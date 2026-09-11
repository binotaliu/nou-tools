<?php

use App\Settings\StudyRoomSettings;

it('resolves with the seeded defaults', function () {
    $settings = app(StudyRoomSettings::class);

    expect($settings->announcement)->not->toBeEmpty()
        ->and($settings->forbiddenNicknames)->toBe([])
        ->and($settings->isOpen)->toBeTrue();
});

it('persists changes when saved', function () {
    $settings = app(StudyRoomSettings::class);

    $settings->forbiddenNicknames = ['壞暱稱'];
    $settings->isOpen = false;
    $settings->save();

    app()->forgetInstance(StudyRoomSettings::class);
    $fresh = app(StudyRoomSettings::class);

    expect($fresh->forbiddenNicknames)->toBe(['壞暱稱'])
        ->and($fresh->isOpen)->toBeFalse();
});
