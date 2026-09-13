<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Image extends Model
{
    protected $fillable = [
        'user_id',
        'original_path',
        'processed_path',
        'prompt',
        'status',
        'pipeline_version',
        'policy_version',
        'policy_hash',
        'template_version',
        'mode',
        'segmentation_status',
        'validator_status',
        'merchant_review',
        'is_infra_error',
        'retry_attempts_used',
    ];

    protected $casts = [
        'is_infra_error' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function processingReasons(): HasMany
    {
        return $this->hasMany(ImageProcessingReason::class);
    }

    public function segmentationReasons(): HasMany
    {
        return $this->processingReasons()->where('stage', 'segmentation');
    }

    public function validatorReasons(): HasMany
    {
        return $this->processingReasons()->where('stage', 'validator');
    }

    /**
     * Replace this image's stored reasons for one stage with the given
     * ReasonCode list - ai-service/standard/reason_codes.py is the source
     * of truth for valid values, kept as plain strings here (see migration
     * comment) rather than re-declared as a Laravel enum.
     */
    public function syncReasons(string $stage, array $reasonCodes): void
    {
        $this->processingReasons()->where('stage', $stage)->delete();

        foreach ($reasonCodes as $reasonCode) {
            $this->processingReasons()->create([
                'stage' => $stage,
                'reason_code' => $reasonCode,
            ]);
        }
    }
}
