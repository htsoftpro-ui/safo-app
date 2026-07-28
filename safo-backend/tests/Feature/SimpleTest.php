<?php

namespace Tests\Feature;

use Tests\TestCase;

class SimpleTest extends TestCase
{
    public function test_health_check(): void
    {
        $response = $this->getJson('/api/v1/health');
        $response->assertOk();
    }
}
