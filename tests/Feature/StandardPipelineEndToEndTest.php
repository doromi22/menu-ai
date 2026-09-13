<?php

namespace Tests\Feature;

use App\Domain\Image\ProcessingOutcome;
use App\Models\Image;
use App\Services\StandardAiService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\Process\Process;
use Tests\TestCase;

/**
 * Real cross-process, cross-language end-to-end test (§11-4): starts an
 * actual `uvicorn` process serving ai-service/standard/api/main.py over
 * real HTTP (not an in-process test double), with
 * STANDARD_API_FAKE_SEGMENTATION=1 so it doesn't need real BiRefNet
 * inference (already covered separately - see
 * ai-service/tests/integration/test_birefnet_real_inference.py), then
 * drives it through StandardAiService exactly as production code would
 * and asserts the response actually lands in the database (§11-2's
 * pivot-table storage decision). This checks the WIRING, not pipeline
 * correctness - that's ai-service's own test suite's job.
 */
class StandardPipelineEndToEndTest extends TestCase
{
    use RefreshDatabase;

    private const PORT = 8099;

    private static ?Process $serverProcess = null;

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();

        $aiServiceDir = realpath(__DIR__ . '/../../../ai-service');
        $python = $aiServiceDir . DIRECTORY_SEPARATOR . 'venv' . DIRECTORY_SEPARATOR . 'Scripts' . DIRECTORY_SEPARATOR . 'python.exe';

        self::$serverProcess = new Process(
            [$python, '-m', 'uvicorn', 'standard.api.main:app', '--port', (string) self::PORT],
            $aiServiceDir,
            ['STANDARD_API_FAKE_SEGMENTATION' => '1']
        );
        self::$serverProcess->start();

        self::waitUntilHealthy();
    }

    public static function tearDownAfterClass(): void
    {
        self::$serverProcess?->stop(5);
        self::$serverProcess = null;
        parent::tearDownAfterClass();
    }

    /**
     * Runs from setUpBeforeClass(), i.e. BEFORE Laravel's app container is
     * bootstrapped for this test class - no facades (Http, etc.) are
     * available yet, so this polls with a raw cURL handle instead.
     */
    private static function waitUntilHealthy(): void
    {
        $deadline = microtime(true) + 30;
        $url = 'http://127.0.0.1:' . self::PORT . '/health';

        while (microtime(true) < $deadline) {
            if (self::$serverProcess->isTerminated()) {
                throw new \RuntimeException(
                    "Standard API server exited before becoming healthy:\n" . self::$serverProcess->getErrorOutput()
                );
            }

            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT_MS, 500);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT_MS, 500);
            $result = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($result !== false && $httpCode === 200) {
                return;
            }

            usleep(200_000);
        }

        throw new \RuntimeException(
            "Standard API server (uvicorn) did not become healthy within 30s\n" . self::$serverProcess->getErrorOutput()
        );
    }

    public function test_processing_one_image_persists_the_full_result_to_the_database(): void
    {
        Storage::fake('public');

        $imagePath = $this->makeSyntheticFoodImage();

        $image = Image::create([
            'original_path' => 'images/original/e2e_food.jpg',
            'status' => 'processing',
        ]);

        $service = new StandardAiService('http://127.0.0.1:' . self::PORT);
        $body = $service->process($image, $imagePath);

        $image->refresh();

        $this->assertSame('PASS', $image->segmentation_status);
        $this->assertSame('PASS', $image->validator_status);
        $this->assertFalse($image->is_infra_error);
        $this->assertSame(0, $image->retry_attempts_used);
        $this->assertSame('completed', $image->status);
        $this->assertNotNull($image->processed_path);
        Storage::disk('public')->assertExists($image->processed_path);

        $this->assertSame(0, $image->segmentationReasons()->count());
        $this->assertSame(0, $image->validatorReasons()->count());

        $this->assertSame(ProcessingOutcome::MESSAGE_COMPLETED, ProcessingOutcome::messageFor($body['metadata']));

        @unlink($imagePath);
    }

    private function makeSyntheticFoodImage(): string
    {
        // background (230,230,230) + a distinct food-colored block -
        // matches standard/api/main.py's _FakeThresholdBackend logic.
        $path = tempnam(sys_get_temp_dir(), 'e2e_food') . '.jpg';

        $gd = imagecreatetruecolor(120, 120);
        imagefilledrectangle($gd, 0, 0, 119, 119, imagecolorallocate($gd, 230, 230, 230));
        imagefilledrectangle($gd, 30, 30, 89, 89, imagecolorallocate($gd, 200, 80, 60));
        imagejpeg($gd, $path, 95);
        imagedestroy($gd);

        return $path;
    }
}
