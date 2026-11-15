<?php

declare(strict_types=1);

namespace App\Domain\Scoring;

/**
 * Verified indicators for one application version. Money is integer NGN kobo.
 * null means "not provided / not verified yet" and keeps the assessment incomplete.
 */
final readonly class NeedInputs
{
    public function __construct(
        public ?int $monthlyIncome,
        public ?int $tuition,
        public ?int $priorArrears,
        public ?bool $externalAid,
        public ?int $dependants,
        public ?bool $isOrphan,
        public ?bool $singleParent,
        public ?bool $disability,
        public ?string $riskLevel, // 'high' | 'medium' | 'low' | null (Pending)
    ) {
    }
}
