<?php

namespace Tests\Feature;

use App\Enums\MerchantRole;
use App\Filament\Pages\ScanCard;
use App\Filament\Pages\Team;
use App\Filament\Resources\LoyaltyCards\LoyaltyCardResource;
use App\Filament\Resources\Orders\OrderResource;
use App\Models\AppSetting;
use App\Models\LoyaltyCard;
use App\Models\LoyaltyProgram;
use App\Models\Merchant;
use App\Models\Order;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Livewire;
use Tests\TestCase;

class AuditFixesTest extends TestCase
{
    use RefreshDatabase;

    private function memberOf(Merchant $merchant, MerchantRole $role): User
    {
        $user = User::factory()->create();
        $merchant->users()->attach($user, ['role' => $role->value]);

        return $user;
    }

    private function actingInPanel(User $user, Merchant $merchant): void
    {
        $this->actingAs($user);
        Filament::setCurrentPanel('merchant');
        Filament::setTenant($merchant);
    }

    public function test_responses_carry_the_security_headers(): void
    {
        $response = $this->get('/app/login');

        $response->assertHeader('X-Frame-Options', 'SAMEORIGIN');
        $response->assertHeader('X-Content-Type-Options', 'nosniff');
        $response->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
        $this->assertStringContainsString("frame-ancestors 'self'", $response->headers->get('Content-Security-Policy'));
    }

    public function test_a_shop_cannot_send_more_than_the_daily_invitation_limit(): void
    {
        Mail::fake();
        config(['mail.default' => 'smtp']);
        AppSetting::storeMail(['host' => 'smtp.example.com', 'port' => 587, 'encryption' => 'tls', 'from_address' => 'hello@example.com', 'from_name' => 'T3al']);
        $shop = Merchant::factory()->create();
        $owner = $this->memberOf($shop, MerchantRole::Owner);
        $this->actingInPanel($owner, $shop);

        for ($i = 0; $i < Team::INVITES_PER_DAY; $i++) {
            RateLimiter::hit('team-invites:'.$shop->id, 86400);
        }

        Livewire::test(Team::class)->callAction('invite', ['email' => 'late@example.com']);

        Mail::assertNothingSent();
    }

    public function test_the_owner_can_renew_a_card_link_and_the_old_link_stops_working(): void
    {
        $shop = Merchant::factory()->create();
        $program = LoyaltyProgram::factory()->create(['merchant_id' => $shop->id]);
        $card = LoyaltyCard::factory()->create(['merchant_id' => $shop->id, 'loyalty_program_id' => $program->id]);
        $oldCode = $card->code;
        $owner = $this->memberOf($shop, MerchantRole::Owner);
        $this->actingInPanel($owner, $shop);

        $this->assertTrue(LoyaltyCardResource::ownerCanRotate());
        $card->rotateCode();

        $this->get("/c/{$oldCode}")->assertNotFound();
        $this->get('/c/'.$card->fresh()->code)->assertOk();
    }

    public function test_staff_cannot_renew_a_card_link(): void
    {
        $shop = Merchant::factory()->create();
        $staff = $this->memberOf($shop, MerchantRole::Staff);
        $this->actingInPanel($staff, $shop);

        $this->assertFalse(LoyaltyCardResource::ownerCanRotate());
    }

    public function test_card_lookups_are_limited_per_staff_member(): void
    {
        $shop = Merchant::factory()->create();
        $staff = $this->memberOf($shop, MerchantRole::Staff);
        $this->actingInPanel($staff, $shop);

        for ($i = 0; $i < 30; $i++) {
            RateLimiter::hit('lookup:'.$staff->id, 60);
        }

        Livewire::test(ScanCard::class)->set('search', 'ANYCODE123')->call('lookup')->assertSet('cardId', null);
    }

    public function test_is_admin_cannot_be_set_through_mass_assignment(): void
    {
        $user = User::create(['name' => 'Mallory', 'email' => 'mallory@example.com', 'password' => 'a-strong-pass', 'is_admin' => true]);

        $this->assertFalse($user->fresh()->is_admin);
    }

    public function test_the_public_card_page_is_rate_limited(): void
    {
        $shop = Merchant::factory()->create();
        $program = LoyaltyProgram::factory()->create(['merchant_id' => $shop->id]);
        $card = LoyaltyCard::factory()->create(['merchant_id' => $shop->id, 'loyalty_program_id' => $program->id]);

        for ($i = 0; $i < 60; $i++) {
            $this->get("/c/{$card->code}");
        }

        $this->get("/c/{$card->code}")->assertStatus(429);
    }

    public function test_a_typed_order_number_cannot_repeat_within_the_day(): void
    {
        $shop = Merchant::factory()->create();
        Order::factory()->create(['merchant_id' => $shop->id, 'number' => 4]);

        $this->assertTrue(OrderResource::numberTakenToday($shop, 4));
        $this->assertFalse(OrderResource::numberTakenToday($shop, 5));
    }
}
