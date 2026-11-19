<?php

declare(strict_types=1);

namespace App\Domain\Scoring;

final readonly class NeedResult
{
    /**
     * @param  array<string, int|null>  $points  income, costs, support, dependants, circumstances, risk
     * @param  list<string>  $missing  indicators that keep the assessment incomplete
     */
    public function __construct(
        public string $policyVersion,
        public string $status,      // 'complete' | 'incomplete'
        public array $points,
        public ?int $total,         // null while incomplete (never a silent zero)
        public ?string $category,   // 'high' | 'moderate' | 'low' | null
        public bool $eligible,
        public array $missing,
    ) {}
}
