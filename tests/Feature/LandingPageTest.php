<?php

namespace Tests\Feature;

use Tests\TestCase;

class LandingPageTest extends TestCase
{
    public function test_landing_page_renders_aurex_marketing_content(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertSeeText('Upgrade Your Look');
        $response->assertSeeText('Try AUREX');
        $response->assertSeeText('Upload Your Selfie');
        $response->assertSeeText('How it works');
    }

    public function test_login_page_renders(): void
    {
        $this->get('/login')
            ->assertStatus(200)
            ->assertSeeText('Welcome back')
            ->assertSeeText('Continue with Google');
    }

    public function test_register_page_renders(): void
    {
        $this->get('/register')
            ->assertStatus(200)
            ->assertSeeText('Create your account');
    }

    public function test_dashboard_requires_auth(): void
    {
        $this->get('/dashboard')->assertRedirect('/login');
    }
}
