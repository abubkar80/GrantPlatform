<?php

use App\Domain\Scoring\NeedInputs;
use App\Domain\Scoring\NeedScorer;
use App\Domain\Scoring\ScoringPolicy;

/** Baseline = zero points in every dimension, so each test changes only what it checks. */
function needInputs(array $overrides = []): NeedInputs
{
    return new NeedInputs(...array_merge([
        'monthlyIncome' => 300_000 * 100,
        'tuition' => 0,
        'priorArrears' => 0,
        'externalAid' => true,
        'dependants' => 0,
        'isOrphan' => false,
        'singleParent' => false,
        'disability' => false,
        'riskLevel' => 'low',
    ], $overrides));
}

function scoreOf(array $overrides = [], ?ScoringPolicy $policy = null)
{
    return (new NeedScorer)->score(needInputs($overrides), $policy);
}

it('scores monthly income at the band edges', function (int $naira, int $expected) {
    expect(scoreOf(['monthlyIncome' => $naira * 100])->points['income'])->toBe($expected);
})->with([
    'zero' => [0, 30],
    'top of 30 band' => [50_000, 30],
    'just above 50k' => [50_001, 20],
    'top of 20 band' => [100_000, 20],
    'just above 100k' => [100_001, 10],
    'top of 10 band' => [200_000, 10],
    'just above 200k' => [200_001, 0],
]);

it('scores tuition plus arrears at the band edges', function (int $tuition, int $arrears, int $expected) {
    $r = scoreOf(['tuition' => $tuition * 100, 'priorArrears' => $arrears * 100]);
    expect($r->points['costs'])->toBe($expected);
})->with([
    'below 100k' => [99_999, 0, 0],
    'exactly 100k' => [100_000, 0, 10],
    'just below 200k' => [150_000, 49_999, 10],
    'exactly 200k' => [150_000, 50_000, 20],
    'just below 300k' => [299_999, 0, 20],
    'exactly 300k' => [200_000, 100_000, 25],
]);

it('scores dependants', function (int $count, int $expected) {
    expect(scoreOf(['dependants' => $count])->points['dependants'])->toBe($expected);
})->with([[0, 0], [2, 0], [3, 5], [5, 5], [6, 10], [9, 10]]);

it('gives existing support 15 only when there is no other aid, and aid never disqualifies', function () {
    expect(scoreOf(['externalAid' => false])->points['support'])->toBe(15);

    $withAid = scoreOf(['externalAid' => true, 'monthlyIncome' => 0, 'tuition' => 300_000 * 100, 'dependants' => 6]);
    expect($withAid->points['support'])->toBe(0)->and($withAid->eligible)->toBeTrue();
});

it('caps combined circumstances at 10', function () {
    $all = scoreOf(['isOrphan' => true, 'singleParent' => true, 'disability' => true]);
    expect($all->points['circumstances'])->toBe(10);
    expect(scoreOf(['singleParent' => true])->points['circumstances'])->toBe(10);
    expect(scoreOf()->points['circumstances'])->toBe(0);
});

it('scores discontinuation risk', function (string $level, int $expected) {
    expect(scoreOf(['riskLevel' => $level])->points['risk'])->toBe($expected);
})->with([['high', 10], ['medium', 5], ['low', 0]]);

it('reaches 100 with every maximum', function () {
    $r = scoreOf([
        'monthlyIncome' => 0, 'tuition' => 300_000 * 100, 'externalAid' => false,
        'dependants' => 6, 'isOrphan' => true, 'riskLevel' => 'high',
    ]);
    expect($r->total)->toBe(100)->and($r->category)->toBe('high')->and($r->eligible)->toBeTrue();
});

it('applies the provisional category bands and the minimum of 35', function (array $o, int $total, string $category, bool $eligible) {
    $r = scoreOf($o);
    expect($r->total)->toBe($total)->and($r->category)->toBe($category)->and($r->eligible)->toBe($eligible);
})->with([
    'income only = 30 is Low, not eligible' => [['monthlyIncome' => 0], 30, 'low', false],
    '35 is Moderate and eligible' => [['monthlyIncome' => 0, 'dependants' => 3], 35, 'moderate', true],
    '55 is Moderate' => [['monthlyIncome' => 0, 'tuition' => 300_000 * 100], 55, 'moderate', true],
    '60 is High' => [['monthlyIncome' => 0, 'tuition' => 300_000 * 100, 'dependants' => 3], 60, 'high', true],
]);

it('keeps the assessment incomplete when evidence is missing instead of awarding zero', function (string $field, string $missingKey) {
    $r = scoreOf([$field => null]);
    expect($r->status)->toBe('incomplete')
        ->and($r->total)->toBeNull()
        ->and($r->category)->toBeNull()
        ->and($r->eligible)->toBeFalse()
        ->and($r->missing)->toContain($missingKey);
})->with([
    'risk still pending' => ['riskLevel', 'risk_level'],
    'income missing' => ['monthlyIncome', 'monthly_income'],
    'tuition missing' => ['tuition', 'tuition'],
    'arrears missing' => ['priorArrears', 'prior_arrears'],
    'aid unknown' => ['externalAid', 'external_aid'],
    'dependants unknown' => ['dependants', 'dependants'],
]);

it('treats undetermined circumstances as missing, but one confirmed circumstance is enough', function () {
    expect(scoreOf(['isOrphan' => null])->missing)->toContain('circumstances');
    expect(scoreOf(['isOrphan' => null, 'disability' => true])->status)->toBe('complete');
});

it('stores the policy version and honours a different policy', function () {
    $strict = new ScoringPolicy('v2', minimumScore: 40, highMinimum: 70);
    $r = scoreOf(['monthlyIncome' => 0, 'dependants' => 3], $strict); // 35 points

    expect($r->policyVersion)->toBe('v2')->and($r->eligible)->toBeFalse()->and($r->category)->toBe('low');
});

it('rejects unknown risk levels and negative amounts', function () {
    expect(fn () => scoreOf(['riskLevel' => 'extreme']))->toThrow(InvalidArgumentException::class);
    expect(fn () => scoreOf(['monthlyIncome' => -1]))->toThrow(InvalidArgumentException::class);
});
