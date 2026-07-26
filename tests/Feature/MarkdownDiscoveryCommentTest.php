<?php

use App\Enums\ArticleType;

test('home page comment points agents to llms.txt', function () {
    $response = $this->get(route('home'));

    $response->assertSuccessful()
        ->assertSee('Markdown version of this page: '.route('llms-txt'), false)
        ->assertSee('Site-wide index for agents: '.route('llms-txt'), false);
});

test('page with a markdown counterpart advertises its markdown url in a comment', function () {
    $response = $this->get(route('articles.index', ['type' => ArticleType::MANUAL->value]));

    $response->assertSuccessful()
        ->assertSee('Markdown version of this page: '.route('articles.index.md', ['type' => ArticleType::MANUAL->value]), false);
});

test('page without a markdown counterpart omits the per-page markdown line', function () {
    $response = $this->get(route('schedules.create'));

    $response->assertSuccessful()
        ->assertDontSee('Markdown version of this page:', false)
        ->assertSee('Site-wide index for agents: '.route('llms-txt'), false);
});
