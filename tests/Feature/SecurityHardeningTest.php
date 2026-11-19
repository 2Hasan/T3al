<?php

namespace Tests\Feature;

use App\Enums\MerchantRole;
use App\Enums\OrderStatus;
use App\Filament\Pages\Team;
use App\Filament\Resources\Orders\OrderResource;
use App\Mail\TeamInvitation;
use App\Models\AppSetting;
use App\Models\Invitation;
use App\Models\LoyaltyCard;
use App\Models\LoyaltyProgram;
use App\Models\Merchant;
use App\Models\Order;
use App\Models\Plan;
use App\Models\User;
use App\Support\MailSettings;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class SecurityHardeningTest extends TestCase
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

    private function saveSmtp(array $overrides = []): void
    {
        AppSetting::storeMail(array_merge([
            'host' => 'smtp.example.com',
            'port' => 587,
            'encryption' => 'tls',
            'username' => 'mailer',
            'password' => 'secret-pass',
            'from_address' => 'hello@example.com',
            'from_name' => 'T3al',
        ], $overrides));
    }

    public function test_staff_cannot_remove_a_member_by_calling_the_action_directly(): void
    {
        $shop = Merchant::factory()->create();
        $staff = $this->memberOf($shop, MerchantRole::Staff);
        $other = $this->memberOf($shop, MerchantRole::Staff);
        $this->actingInPanel($staff, $shop);

        $this->expectException(HttpException::class);
        (new Team)->removeMember($other->id);
    }

    public function test_staff_cannot_revoke_an_invitation_by_calling_the_action_directly(): void
    {
        $shop = Merchant::factory()->create();
        $owner = $this->memberOf($shop, MerchantRole::Owner);
        $staff = $this->memberOf($shop, MerchantRole::Staff);
        Invitation::issue($shop, 'pending@example.com', MerchantRole::Staff, $owner);
        $invitation = Invitation::sole();
        $this->actingInPanel($staff, $shop);

        try {
            (new Team)->revoke($invitation->id);
            $this->fail('Staff should not be able to revoke invitations.');
        } catch (HttpException $e) {
            $this->assertSame(403, $e->getStatusCode());
        }

        $this->assertTrue(Invitation::whereKey($invitation->id)->exists());
    }

    public function test_an_invitation_is_not_sent_when_email_is_not_configured(): void
    {
        Mail::fake();
        config(['mail.default' => 'log']);
        $shop = Merchant::factory()->create();
        $owner = $this->memberOf($shop, MerchantRole::Owner);
        $this->actingInPanel($owner, $shop);

        $this->assertFalse(MailSettings::isConfigured());

        Livewire::test(Team::class)->callAction('invite', ['email' => 'new@example.com']);

        Mail::assertNothingSent();
    }

    public function test_an_invitation_is_sent_once_smtp_is_saved(): void
    {
        Mail::fake();
        $this->saveSmtp();
        MailSettings::apply();
        $shop = Merchant::factory()->create();
        $owner = $this->memberOf($shop, MerchantRole::Owner);
        $this->actingInPanel($owner, $shop);

        $this->assertTrue(MailSettings::isConfigured());

        Livewire::test(Team::class)->callAction('invite', ['email' => 'new@example.com']);

        Mail::assertSent(TeamInvitation::class, fn (TeamInvitation $mail) => $mail->hasTo('new@example.com'));
    }

    public function test_smtp_password_is_stored_encrypted(): void
    {
        $this->saveSmtp(['password' => 'very-secret']);

        $raw = \DB::table('app_settings')->where('key', AppSetting::MAIL)->value('value');

        $this->assertStringNotContainsString('very-secret', $raw);
        $this->assertSame('very-secret', AppSetting::mail()['password']);
    }

    public function test_an_empty_password_on_save_keeps_the_stored_one(): void
    {
        $this->saveSmtp(['password' => 'keep-me']);

        AppSetting::storeMail(array_merge(AppSetting::mail(), ['password' => AppSetting::mail()['password']]));

        $this->assertSame('keep-me', AppSetting::mail()['password']);
    }

    public function test_applying_saved_settings_configures_the_smtp_mailer(): void
    {
        $this->saveSmtp(['host' => 'mail.shop.test', 'port' => 2525]);

        MailSettings::apply();

        $this->assertSame('smtp', config('mail.default'));
        $this->assertSame('mail.shop.test', config('mail.mailers.smtp.host'));
        $this->assertSame(2525, config('mail.mailers.smtp.port'));
        $this->assertSame('hello@example.com', config('mail.from.address'));
    }

    public function test_the_apple_wallet_link_is_not_served_without_loyalty_cards_in_the_plan(): void
    {
        config(['mobile-pass.apple.certificate_path' => '/path/to/certificate.p12']);
        $plan = Plan::factory()->create(['features' => ['order_queue']]);
        $merchant = Merchant::factory()->create(['plan_id' => $plan->id]);
        $program = LoyaltyProgram::factory()->create(['merchant_id' => $merchant->id]);
        $card = LoyaltyCard::factory()->create(['merchant_id' => $merchant->id, 'loyalty_program_id' => $program->id]);

        $this->get(route('cards.apple', $card))->assertNotFound();
    }

    public function test_staff_cannot_open_orders_when_the_plan_lacks_the_feature(): void
    {
        $plan = Plan::factory()->create(['features' => ['loyalty_cards']]);
        $shop = Merchant::factory()->create(['plan_id' => $plan->id]);
        $staff = $this->memberOf($shop, MerchantRole::Staff);
        Order::factory()->create(['merchant_id' => $shop->id, 'status' => OrderStatus::Preparing]);
        $this->actingInPanel($staff, $shop);

        $this->assertFalse(OrderResource::canAccess());
        $this->actingAs($staff)->get("/app/{$shop->slug}/orders")->assertForbidden();
    }
}
