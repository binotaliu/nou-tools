<?php

test('offline page is a self-contained fallback', function () {
    $this->get(route('offline'))
        ->assertStatus(200)
        ->assertSee('目前無法連線')
        ->assertDontSee('data-page', false)
        ->assertDontSee('/build/', false);
});
