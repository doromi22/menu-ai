<?php

namespace Tests\Unit;

use App\Services\StandardAiService;
use Tests\TestCase;

/**
 * Verifies StandardAiService::userMessageFor()'s branch ORDER specifically:
 * is_infra_error must be checked first and return immediately, so it can
 * never be overwritten by the generic segmentation_status === 'REJECT'
 * message even though an infra failure also carries segmentation_status
 * = 'REJECT' (see standard/segmentation/README.md - is_infra_error is
 * just an additional flag on top of a REJECT, not a different status).
 */
class StandardAiServiceMessageMappingTest extends TestCase
{
    private StandardAiService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new StandardAiService();
    }

    public function test_infra_error_message_wins_even_though_segmentation_status_is_also_reject(): void
    {
        $metadata = [
            'is_infra_error' => true,
            'segmentation_status' => 'REJECT', // an infra failure IS a REJECT - this must not win
            'validator_status' => null,
        ];

        $this->assertSame(
            '일시적인 오류가 발생했습니다. 잠시 후 다시 시도해주세요.',
            $this->service->userMessageFor($metadata)
        );
    }

    public function test_data_quality_reject_message_when_not_an_infra_error(): void
    {
        $metadata = [
            'is_infra_error' => false,
            'segmentation_status' => 'REJECT',
            'validator_status' => null,
        ];

        $this->assertSame(
            '이 사진은 지원되지 않습니다. 다른 사진으로 시도해주세요.',
            $this->service->userMessageFor($metadata)
        );
    }

    public function test_review_message_for_segmentation_review(): void
    {
        $metadata = [
            'is_infra_error' => false,
            'segmentation_status' => 'REVIEW',
            'validator_status' => 'PASS',
        ];

        $this->assertSame(
            '검토가 필요한 이미지입니다. 확인 후 안내드리겠습니다.',
            $this->service->userMessageFor($metadata)
        );
    }

    public function test_review_message_for_validator_review(): void
    {
        $metadata = [
            'is_infra_error' => false,
            'segmentation_status' => 'PASS',
            'validator_status' => 'REVIEW',
        ];

        $this->assertSame(
            '검토가 필요한 이미지입니다. 확인 후 안내드리겠습니다.',
            $this->service->userMessageFor($metadata)
        );
    }

    public function test_completed_message_for_a_clean_pass(): void
    {
        $metadata = [
            'is_infra_error' => false,
            'segmentation_status' => 'PASS',
            'validator_status' => 'PASS',
        ];

        $this->assertSame('처리가 완료되었습니다.', $this->service->userMessageFor($metadata));
    }
}
