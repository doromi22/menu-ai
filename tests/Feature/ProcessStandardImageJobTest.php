<?php

namespace Tests\Feature;

use App\Jobs\ProcessImageJob;
use App\Jobs\ProcessStandardImageJob;
use App\Models\Image;
use App\Models\User;
use App\Services\StandardAiService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Upload -> Standard pipeline wiring. The HTTP contract itself is covered
 * by StandardPipelineEndToEndTest against a real uvicorn process; these
 * fake the HTTP layer and check the queue/credit/status behaviour around it.
 */
class ProcessStandardImageJobTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
    }

    public function test_upload_dispatches_the_standard_job_with_the_preset_template_by_default(): void
    {
        Queue::fake();
        $user = User::factory()->create(['credits' => 3]);

        $response = $this->actingAs($user)->postJson('/api/images/upload', [
            'image' => UploadedFile::fake()->image('food.jpg', 400, 400),
            'prompt' => 'ramen',
        ]);

        $response->assertCreated();
        Queue::assertPushed(ProcessStandardImageJob::class, fn ($job) => $job->templateId === 'T04_dark_premium');
        Queue::assertNotPushed(ProcessImageJob::class);
        $this->assertSame(2, $user->fresh()->credits);
    }

    public function test_unmapped_preset_leaves_template_to_the_api_default(): void
    {
        Queue::fake();
        $user = User::factory()->create(['credits' => 1]);

        $this->actingAs($user)->postJson('/api/images/upload', [
            'image' => UploadedFile::fake()->image('food.jpg', 400, 400),
            'prompt' => 'something-else',
        ])->assertCreated();

        Queue::assertPushed(ProcessStandardImageJob::class, fn ($job) => $job->templateId === null);
    }

    public function test_premium_mode_still_dispatches_the_modal_job(): void
    {
        Queue::fake();
        config(['services.image_processing.mode' => 'premium']);
        $user = User::factory()->create(['credits' => 1]);

        $this->actingAs($user)->postJson('/api/images/upload', [
            'image' => UploadedFile::fake()->image('food.jpg', 400, 400),
            'prompt' => 'cafe',
        ])->assertCreated();

        Queue::assertPushed(ProcessImageJob::class);
        Queue::assertNotPushed(ProcessStandardImageJob::class);
    }

    public function test_pass_result_completes_without_refund(): void
    {
        Http::fake(['*/v1/standard/process' => Http::response($this->apiBody('PASS', withImage: true))]);
        [$user, $image] = $this->pendingImage(credits: 2);

        (new ProcessStandardImageJob($image, 'T04_dark_premium'))->handle(new StandardAiService('http://ai.test'));

        $image->refresh();
        $this->assertSame('completed', $image->status);
        Storage::disk('public')->assertExists($image->processed_path);
        $this->assertSame(2, $user->fresh()->credits);
        Http::assertSent(fn ($request) => collect($request->data())->contains(
            fn ($part) => ($part['name'] ?? null) === 'template_id' && ($part['contents'] ?? null) === 'T04_dark_premium'
        ));
    }

    public function test_reject_result_fails_and_refunds_the_credit(): void
    {
        Http::fake(['*/v1/standard/process' => Http::response($this->apiBody('REJECT', withImage: false))]);
        [$user, $image] = $this->pendingImage(credits: 2);

        (new ProcessStandardImageJob($image))->handle(new StandardAiService('http://ai.test'));

        $this->assertSame('failed', $image->fresh()->status);
        $this->assertSame(3, $user->fresh()->credits);
    }

    public function test_unreachable_service_fails_and_refunds_the_credit(): void
    {
        Http::fake(fn () => throw new ConnectionException('connection refused'));
        [$user, $image] = $this->pendingImage(credits: 0);

        (new ProcessStandardImageJob($image))->handle(new StandardAiService('http://ai.test'));

        $this->assertSame('failed', $image->fresh()->status);
        $this->assertSame(1, $user->fresh()->credits);
    }

    /** @return array{0: User, 1: Image} */
    private function pendingImage(int $credits): array
    {
        $user = User::factory()->create(['credits' => $credits]);
        Storage::disk('public')->put('images/original/food.jpg', UploadedFile::fake()->image('food.jpg')->getContent());

        $image = Image::create([
            'user_id' => $user->id,
            'original_path' => 'images/original/food.jpg',
            'status' => 'pending',
        ]);

        return [$user, $image];
    }

    private function apiBody(string $segmentationStatus, bool $withImage): array
    {
        $body = [
            'metadata' => [
                'pipeline_version' => 'standard_1.0.0',
                'policy_version' => 'food_integrity_1.0.1',
                'policy_hash' => 'sha256:test',
                'template_version' => 'graphic_1.0.0',
                'mode' => 'standard',
                'segmentation_status' => $segmentationStatus,
                'segmentation_reasons' => $segmentationStatus === 'REJECT' ? ['SEGMENTATION_AREA_TOO_SMALL'] : [],
                'is_infra_error' => false,
                'validator_status' => $segmentationStatus === 'REJECT' ? null : 'PASS',
                'validator_reasons' => [],
                'retry_attempts_used' => 0,
                'merchant_review' => null,
            ],
        ];

        if ($withImage) {
            $body['image'] = ['content_type' => 'image/jpeg', 'encoding' => 'base64', 'data' => base64_encode('fake-jpeg-bytes')];
        }

        return $body;
    }
}
