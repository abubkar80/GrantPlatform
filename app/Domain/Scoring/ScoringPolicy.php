<?php

declare(strict_types=1);

namespace App\Domain\Scoring;

/** A named, versioned set of scoring settings (SRS 12: min 35, High from 60 are provisional demo values). */
final readonly class ScoringPolicy
{
    public function __construct(
        public string $version = 'demo-1',
        public int $minimumScore = 35,
        public int $highMinimum = 60,
    ) {
    }
}
