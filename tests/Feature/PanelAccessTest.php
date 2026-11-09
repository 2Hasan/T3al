<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PanelAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_sent_to_the_shop_login(): void
    {
        $this->get('/app')->assertRedirect('/app/login');
    }

    public function test_a_shop_user_cannot_open_the_admin_panel(): void
    {
        $user = User::factory()->create(['is_admin' => false]);

        $this->actingAs($user)->get('/admin')->assertForbidden();
    }

    public function test_an_admin_can_open_the_admin_panel(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $this->actingAs($admin)->get('/admin')->assertOk();
    }

    public function test_locale_choice_is_remembered_in_the_session(): void
    {
        $this->get('/locale/en');

        $this->assertSame('en', session('locale'));
    }
}
