<?php

use App\Models\Course;

// coursemap.nou.edu.tw errors on a detail page until its homepage has set a
// session cookie, so the link warms the session in the tab it opens (see
// resources/js/Composables/useCoursemapLink.js). window.open is stubbed: the
// real site is not something a test should depend on.

it('opens the coursemap homepage first, then points the same tab at the description', function () {
    $course = Course::factory()->create([
        'description_url' => 'https://coursemap.nou.edu.tw/sp.asp?xdurl=mp1ap/CourseDetail.asp&ctNode=1051&xitem=1',
    ]);

    $page = visit(route('course.show', $course));

    $page->script(<<<'JS'
        window.__tab = { closed: false, location: { href: '' }, opener: {} };
        window.__opened = [];
        window.open = (url) => { window.__opened.push(url); return window.__tab; };
    JS);

    $page->click('[data-testid="course-description-link"]');

    expect($page->script('window.__opened'))->toContain('https://coursemap.nou.edu.tw/');

    $page->wait(2);

    expect($page->script('window.__tab.location.href'))
        ->toBe('https://coursemap.nou.edu.tw/sp.asp?xdurl=mp1ap/CourseDetail.asp&ctNode=1051&xitem=1');
});
