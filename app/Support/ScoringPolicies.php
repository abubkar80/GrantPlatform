<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;
use stdClass;

/** The published scoring policy row that assessments point at (SRS: weights 30/25/15/10/10/10, demo cut-offs 35 and 60). */
final class ScoringPolicies
{
    public static function current(int $adminId): stdClass
    {
        $existing = DB::table('scoring_policies')->where('status', 'published')->orderByDesc('id')->first();
        if ($existing !== null) {
            return $existing;
        }

        $now = now();
        $id = DB::table('scoring_policies')->insertGetId([
            'version' => 'demo-1',
            'status' => 'published',
            'rules' => json_encode([
                'weights' => ['income' => 30, 'costs' => 25, 'support' => 15, 'dependants' => 10, 'circumstances' => 10, 'risk' => 10],
            ], JSON_THROW_ON_ERROR),
            'effective_at' => $now,
            'created_by' => $adminId,
            'minimum_score' => 35,
            'high_minimum' => 60,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        return DB::table('scoring_policies')->where('id', $id)->sole();
    }
}
