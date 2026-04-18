<?php

it('responds to the Laravel health check endpoint', function () {
    $response = $this->get('/up');

    $response->assertOk();
});
