<?php

test('clear-cache route no longer exists', function () {
    $response = $this->get('/clear-cache');

    $response->assertNotFound();
});
