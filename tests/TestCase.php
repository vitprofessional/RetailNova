<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;

    /**
     * A cross-business request must be refused without touching data. With
     * tenant scoping the foreign record is simply invisible, so the refusal may
     * surface as 404 (not found), 422 (failed exists rule), 403 or a redirect back.
     */
    protected function assertDenied(\Illuminate\Testing\TestResponse $response): \Illuminate\Testing\TestResponse
    {
        $this->assertContains($response->getStatusCode(), [302, 403, 404, 422],
            'Expected the cross-business request to be refused, got '.$response->getStatusCode());
        return $response;
    }

}
