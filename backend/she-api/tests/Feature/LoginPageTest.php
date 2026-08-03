<?php

namespace Tests\Feature;

use Tests\TestCase;

class LoginPageTest extends TestCase
{
    public function test_login_page_uses_ecogreen_logo(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('id="loginBrandLogo"', false)
            ->assertSee('src="/images/ecogreen-logo-print.png"', false)
            ->assertSee('alt="Ecogreen Oleochemicals"', false)
            ->assertDontSee('fas fa-hard-hat', false);

        $this->assertFileExists(public_path('images/ecogreen-logo-print.png'));
    }
}
