<?php

use App\Enums\StudyActivityVerb;

it('formats each activity verb into its Traditional Chinese sentence shape', function () {
    $subject = '普通物理學';

    expect(StudyActivityVerb::ExamPrep->format($subject))->toBe('正在準備普通物理學考試')
        ->and(StudyActivityVerb::Reading->format($subject))->toBe('正在讀普通物理學')
        ->and(StudyActivityVerb::Homework->format($subject))->toBe('寫普通物理學的作業')
        ->and(StudyActivityVerb::Review->format($subject))->toBe('正在複習普通物理學');
});

it('provides labels for every case', function () {
    $labels = StudyActivityVerb::getLabels();

    expect($labels)->toHaveCount(count(StudyActivityVerb::cases()));

    foreach (StudyActivityVerb::cases() as $case) {
        expect($labels)->toHaveKey($case->value)
            ->and($labels[$case->value])->toBe($case->label());
    }
});
