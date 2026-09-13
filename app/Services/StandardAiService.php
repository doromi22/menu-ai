<?php

namespace App\Services;

use App\Models\Image;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Calls the Standard pipeline's own API (ai-service/standard/api/main.py,
 * §11 step 4) - a deliberately separate service from the existing
 * AiGenerationService/ProcessImageJob (Premium, GPU, Modal-hosted). See
 * ai-service/docs/standard-api-contract.md for why they're kept apart.
 */
class StandardAiService
{
    protected string $baseUrl;

    /**
     * 60s starting point, not derived from a measured SLA - see
     * ai-service/docs/standard-api-contract.md's "Timeout" section for why
     * (CPU-only measurements, no GPU-environment data yet, full pipeline
     * cost including possible Retry re-renders is more than just the
     * Segmentation Gate's own ~9.7s typical / 34.75s worst-case).
     */
    protected int $timeoutSeconds = 60;

    public function __construct(?string $baseUrl = null)
    {
        $this->baseUrl = rtrim($baseUrl ?? env('STANDARD_AI_SERVICE_URL', 'http://127.0.0.1:8002'), '/');
    }

    /**
     * Runs one image through the Standard pipeline and persists the
     * result onto $image (scalar columns + image_processing_reasons
     * pivot rows, §11-2's storage decision). Returns the decoded response
     * body in case the caller wants more than what got persisted.
     *
     * @throws \Illuminate\Http\Client\ConnectionException on a transport-level
     *         failure (service unreachable, DNS, timeout) - distinct from a
     *         pipeline-level infra error, which comes back as an ordinary
     *         200 response with metadata.is_infra_error = true (see
     *         userMessageFor()).
     * @throws \Illuminate\Http\Client\RequestException on a 4xx/5xx from
     *         the service itself (e.g. unreadable image, bad template_id).
     */
    public function process(Image $image, string $originalFullPath, ?string $templateId = null): array
    {
        $response = Http::timeout($this->timeoutSeconds)
            ->attach('image', file_get_contents($originalFullPath), basename($originalFullPath))
            ->post("{$this->baseUrl}/v1/standard/process", array_filter([
                'template_id' => $templateId,
            ]));

        $response->throw();

        $body = $response->json();
        $this->persist($image, $body);

        return $body;
    }

    protected function persist(Image $image, array $body): void
    {
        $metadata = $body['metadata'];

        $image->fill([
            'pipeline_version' => $metadata['pipeline_version'],
            'policy_version' => $metadata['policy_version'],
            'policy_hash' => $metadata['policy_hash'],
            'template_version' => $metadata['template_version'],
            'mode' => $metadata['mode'],
            'segmentation_status' => $metadata['segmentation_status'],
            'validator_status' => $metadata['validator_status'],
            'merchant_review' => $metadata['merchant_review'],
            'is_infra_error' => $metadata['is_infra_error'],
            'retry_attempts_used' => $metadata['retry_attempts_used'],
        ]);

        if (isset($body['image']['data'])) {
            $processedRelativePath = 'images/processed/' . Str::random(40) . '.jpg';
            Storage::disk('public')->put($processedRelativePath, base64_decode($body['image']['data']));
            $image->processed_path = $processedRelativePath;
            $image->status = 'completed'; // usable output per §7, even if validator_status is REVIEW
        } else {
            $image->status = 'failed'; // §7 REJECT (incl. infra error) = "결과물 미사용" - nothing usable
        }

        $image->save();

        $image->syncReasons('segmentation', $metadata['segmentation_reasons']);
        $image->syncReasons('validator', $metadata['validator_reasons']);
    }

    /**
     * Minimal user-facing message mapping (no UI yet - just the string a
     * future UI would show). Kept here rather than in a view/component
     * since it's a direct function of the API response, not presentation
     * logic.
     */
    public function userMessageFor(array $metadata): string
    {
        if ($metadata['is_infra_error'] ?? false) {
            return '일시적인 오류가 발생했습니다. 잠시 후 다시 시도해주세요.';
        }

        if (($metadata['segmentation_status'] ?? null) === 'REJECT') {
            return '이 사진은 지원되지 않습니다. 다른 사진으로 시도해주세요.';
        }

        if (($metadata['segmentation_status'] ?? null) === 'REVIEW' || ($metadata['validator_status'] ?? null) === 'REVIEW') {
            return '검토가 필요한 이미지입니다. 확인 후 안내드리겠습니다.';
        }

        return '처리가 완료되었습니다.';
    }
}
