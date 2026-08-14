<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApiAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_protected_api_returns_json_unauthorized_without_token(): void
    {
        $response = $this->get('/admin/api/variant-options');

        $response
            ->assertUnauthorized()
            ->assertHeader('content-type', 'application/json')
            ->assertExactJson([
                'status_code' => 401,
                'message' => 'Unauthenticated',
            ]);
    }

    public function test_protected_api_returns_json_unauthorized_for_invalid_token(): void
    {
        $response = $this
            ->withToken('invalid-or-expired-token')
            ->get('/admin/api/variant-options');

        $response
            ->assertUnauthorized()
            ->assertExactJson([
                'status_code' => 401,
                'message' => 'Unauthenticated',
            ]);
    }
}
