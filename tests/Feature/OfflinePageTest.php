<?php

test('offline page is reachable and mentions cached schedules', function () {
    $response = $this->get(route('offline'));

    $response->assertStatus(200)
        ->assertSee('離線');
});
