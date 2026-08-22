<?php

namespace Tests\Feature;

use Illuminate\Session\TokenMismatchException;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class ExpiredSessionResponseTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Route::middleware('web')->get('/testing/expired-session', function () {
            throw new TokenMismatchException;
        });
    }

    public function test_web_token_mismatch_redirects_to_login_with_arabic_message(): void
    {
        $this->get('/testing/expired-session')
            ->assertRedirect(route('login'))
            ->assertSessionHas('status', 'انتهت الجلسة، يرجى تسجيل الدخول مرة أخرى.');
    }

    public function test_json_token_mismatch_remains_a_json_419_response(): void
    {
        $this->getJson('/testing/expired-session')
            ->assertStatus(419)
            ->assertHeader('content-type', 'application/json')
            ->assertJsonStructure(['message']);
    }
}
