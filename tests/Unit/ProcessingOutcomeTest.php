<?php

namespace Tests\Unit;

use App\Domain\Image\ProcessingOutcome;
use App\Models\Image;
use Tests\TestCase;

/**
 * Branch ORDER matters: an infrastructure failure also carries
 * segmentation_status = REJECT, so is_infra_error must be checked first or
 * the merchant would be told their photo is unsupported when the service
 * was simply down (see ai-service standard/segmentation/README.md).
 */
class ProcessingOutcomeTest extends TestCase
{
    public function test_infra_error_message_wins_even_though_segmentation_status_is_also_reject(): void
    {
        $this->assertSame(ProcessingOutcome::MESSAGE_INFRA_ERROR, ProcessingOutcome::messageFor([
            'is_infra_error' => true,
            'segmentation_status' => 'REJECT',
            'validator_status' => null,
        ]));
    }

    public function test_data_quality_reject_is_reported_as_unsupported_photo(): void
    {
        $this->assertSame(ProcessingOutcome::MESSAGE_UNSUPPORTED, ProcessingOutcome::messageFor([
            'is_infra_error' => false,
            'segmentation_status' => 'REJECT',
            'validator_status' => null,
        ]));
    }

    public function test_review_from_either_stage_requires_review(): void
    {
        foreach ([['REVIEW', 'PASS'], ['PASS', 'REVIEW']] as [$segmentation, $validator]) {
            $metadata = ['is_infra_error' => false, 'segmentation_status' => $segmentation, 'validator_status' => $validator];

            $this->assertTrue(ProcessingOutcome::requiresReview($metadata));
            $this->assertSame(ProcessingOutcome::MESSAGE_REVIEW, ProcessingOutcome::messageFor($metadata));
        }
    }

    public function test_clean_pass_is_completed(): void
    {
        $metadata = ['is_infra_error' => false, 'segmentation_status' => 'PASS', 'validator_status' => 'PASS'];

        $this->assertFalse(ProcessingOutcome::requiresReview($metadata));
        $this->assertSame(ProcessingOutcome::MESSAGE_COMPLETED, ProcessingOutcome::messageFor($metadata));
    }

    public function test_no_message_while_still_processing(): void
    {
        $this->assertNull(ProcessingOutcome::messageForImage(new Image(['status' => 'pending'])));
        $this->assertNull(ProcessingOutcome::messageForImage(new Image(['status' => 'processing'])));
    }

    public function test_failure_without_pipeline_metadata_is_reported_as_temporary(): void
    {
        // e.g. the AI service was unreachable, so nothing was persisted
        $this->assertSame(
            ProcessingOutcome::MESSAGE_INFRA_ERROR,
            ProcessingOutcome::messageForImage(new Image(['status' => 'failed']))
        );
    }
}
