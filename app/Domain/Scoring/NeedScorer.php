<?php

declare(strict_types=1);

namespace App\Domain\Scoring;

use InvalidArgumentException;

/**
 * Accepted workbook model, SRS section 12 (decision A-2026-10-13). Pure logic: no database, no clock.
 * Max 100 = income 30 + costs 25 + support 15 + dependants 10 + circumstances 10 + risk 10.
 */
final class NeedScorer
{
    private const KOBO = 100;

    public function score(NeedInputs $in, ?ScoringPolicy $policy = null): NeedResult
    {
        $policy ??= new ScoringPolicy();

        foreach ([$in->monthlyIncome, $in->tuition, $in->priorArrears, $in->dependants] as $number) {
            if ($number !== null && $number < 0) {
                throw new InvalidArgumentException('Amounts and counts cannot be negative.');
            }
        }

        $circumstances = $this->circumstances($in);

        $required = [
            'monthly_income' => $in->monthlyIncome,
            'tuition' => $in->tuition,
            'prior_arrears' => $in->priorArrears,
            'external_aid' => $in->externalAid,
            'dependants' => $in->dependants,
            'circumstances' => $circumstances,
            'risk_level' => $in->riskLevel,
        ];
        $missing = array_keys(array_filter($required, fn ($value) => $value === null));

        $points = [
            'income' => $in->monthlyIncome === null ? null : $this->income($in->monthlyIncome),
            'costs' => ($in->tuition === null || $in->priorArrears === null)
                ? null : $this->costs($in->tuition + $in->priorArrears),
            'support' => $in->externalAid === null ? null : ($in->externalAid ? 0 : 15),
            'dependants' => $in->dependants === null ? null : $this->dependants($in->dependants),
            'circumstances' => $circumstances,
            'risk' => $in->riskLevel === null ? null : $this->risk($in->riskLevel),
        ];

        if ($missing !== []) {
            return new NeedResult($policy->version, 'incomplete', $points, null, null, false, $missing);
        }

        $total = array_sum($points);
        $category = $total >= $policy->highMinimum ? 'high' : ($total >= $policy->minimumScore ? 'moderate' : 'low');

        return new NeedResult($policy->version, 'complete', $points, $total, $category, $total >= $policy->minimumScore, []);
    }

    private function income(int $kobo): int
    {
        return match (true) {
            $kobo <= 50_000 * self::KOBO => 30,
            $kobo <= 100_000 * self::KOBO => 20,
            $kobo <= 200_000 * self::KOBO => 10,
            default => 0,
        };
    }

    /** Current tuition plus earlier-period arrears (callers must not double count current charges). */
    private function costs(int $kobo): int
    {
        return match (true) {
            $kobo >= 300_000 * self::KOBO => 25,
            $kobo >= 200_000 * self::KOBO => 20,
            $kobo >= 100_000 * self::KOBO => 10,
            default => 0,
        };
    }

    private function dependants(int $count): int
    {
        return match (true) {
            $count >= 6 => 10,
            $count >= 3 => 5,
            default => 0,
        };
    }

    /** One or more validated circumstances = 10 in total (capped). Undetermined stays null. */
    private function circumstances(NeedInputs $in): ?int
    {
        $flags = [$in->isOrphan, $in->singleParent, $in->disability];
        if (in_array(true, $flags, true)) {
            return 10;
        }

        return in_array(null, $flags, true) ? null : 0;
    }

    private function risk(string $level): int
    {
        return match (strtolower($level)) {
            'high' => 10,
            'medium' => 5,
            'low' => 0,
            default => throw new InvalidArgumentException("Unknown risk level: {$level}"),
        };
    }
}
