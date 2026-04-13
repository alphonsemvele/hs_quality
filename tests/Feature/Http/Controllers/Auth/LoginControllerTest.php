<?php

namespace Tests\Feature\Http\Controllers\Auth;

use Illuminate\Foundation\Testing\WithFaker;
use JMac\Testing\Traits\AdditionalAssertions;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * @see \App\Http\Controllers\Auth\LoginController
 */
final class LoginControllerTest extends TestCase
{
    use AdditionalAssertions, WithFaker;

    #[Test]
    public function login_uses_form_request_validation(): void
    {
        $this->assertActionUsesFormRequest(
            \App\Http\Controllers\Auth\LoginController::class,
            'login',
            \App\Http\Requests\Auth\LoginControllerLoginRequest::class
        );
    }

    #[Test]
    public function login_redirects(): void
    {
        $response = $this->get(route('logins.login'));

        $response->assertRedirect(route('dashboard'));
    }
}
