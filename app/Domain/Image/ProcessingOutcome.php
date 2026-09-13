<?php

namespace App\Domain\Image;

use App\Models\Image;

/**
 * What a processed image means for the merchant, derived from the Standard
 * pipeline's metadata. Order matters: an infrastructure failure also comes
 * back as a segmentation REJECT, and must not be reported as "this photo
 * is not supported".
 */
class ProcessingOutcome
{
    public const MESSAGE_INFRA_ERROR = '一時的なエラーが発生しました。しばらくしてから再度お試しください。';

    public const MESSAGE_UNSUPPORTED = 'この写真には対応していません。別の写真でお試しください。';

    public const MESSAGE_REVIEW = '確認が必要な画像です。確認後にご案内します。';

    public const MESSAGE_COMPLETED = '処理が完了しました。';

    /**
     * @param  array{is_infra_error?: bool, segmentation_status?: ?string, validator_status?: ?string}  $metadata
     */
    public static function messageFor(array $metadata): string
    {
        if ($metadata['is_infra_error'] ?? false) {
            return self::MESSAGE_INFRA_ERROR;
        }

        if (($metadata['segmentation_status'] ?? null) === 'REJECT') {
            return self::MESSAGE_UNSUPPORTED;
        }

        if (self::requiresReview($metadata)) {
            return self::MESSAGE_REVIEW;
        }

        return self::MESSAGE_COMPLETED;
    }

    /**
     * Null while still queued/processing. A failure without pipeline
     * metadata (service unreachable, HTTP error) is treated as temporary.
     */
    public static function messageForImage(Image $image): ?string
    {
        return match ($image->status) {
            'completed' => self::messageFor(self::metadataOf($image)),
            'failed' => $image->segmentation_status === null ? self::MESSAGE_INFRA_ERROR : self::messageFor(self::metadataOf($image)),
            default => null,
        };
    }

    public static function requiresReview(array $metadata): bool
    {
        return ($metadata['segmentation_status'] ?? null) === 'REVIEW'
            || ($metadata['validator_status'] ?? null) === 'REVIEW';
    }

    public static function imageRequiresReview(Image $image): bool
    {
        return self::requiresReview(self::metadataOf($image));
    }

    private static function metadataOf(Image $image): array
    {
        return [
            'is_infra_error' => (bool) $image->is_infra_error,
            'segmentation_status' => $image->segmentation_status,
            'validator_status' => $image->validator_status,
        ];
    }
}
