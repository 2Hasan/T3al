<?php

namespace Tests\Feature;

use App\Enums\MerchantRole;
use App\Filament\Pages\Team;
use App\Mail\TeamInvitation;
use App\Models\Invitation;
use App\Models\Merchant;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;
use Tests\TestCase;

class TeamInvitationTest extends TestCase
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

    /** Pulls the plain token out of the sent invitation email. */
    private function tokenFromMail(): string
    {
        $sent = Mail::sent(TeamInvitation::class)->first();

        return basename(parse_url($sent->acceptUrl, PHP_URL_PATH));
    }

    public function test_only_the_owner_can_open_the_team_page(): void
    {
        $merchant = Merchant::factory()->create();

        $this->actingInPanel($this->memberOf($merchant, MerchantRole::Staff), $merchant);
        $this->assertFalse(Team::canAccess());

        $this->actingInPanel($this->memberOf($merchant, MerchantRole::Owner), $merchant);
        $this->assertTrue(Team::canAccess());
    }

    public function test_the_owner_invites_by_email_and_the_invitation_is_emailed(): void
    {
        Mail::fake();
        $merchant = Merchant::factory()->create();
        $owner = $this->memberOf($merchant, MerchantRole::Owner);
        $this->actingInPanel($owner, $merchant);

        Livewire::test(Team::class)
            ->callAction('invite', ['email' => 'Cashier@Example.com'])
            ->assertHasNoActionErrors();

        Mail::assertSent(TeamInvitation::class, fn (TeamInvitation $mail) => $mail->hasTo('cashier@example.com'));

        $invitation = Invitation::sole();
        $this->assertSame(MerchantRole::Staff, $invitation->role);
        $this->assertNotSame($this->tokenFromMail(), $invitation->token_hash, 'Only the hash is stored.');
    }

    public function test_a_new_person_accepts_the_invitation_and_becomes_staff(): void
    {
        Mail::fake();
        $merchant = Merchant::factory()->create();
        $owner = $this->memberOf($merchant, MerchantRole::Owner);
        $token = Invitation::issue($merchant, 'new@example.com', MerchantRole::Staff, $owner);

        $this->post("/invite/{$token}", [
            'name' => 'Noor',
            'password' => 'a-strong-pass',
            'password_confirmation' => 'a-strong-pass',
        ])->assertRedirect("/app/{$merchant->slug}");

        $user = User::where('email', 'new@example.com')->sole();
        $this->assertSame(MerchantRole::Staff, $merchant->roleOf($user));
        $this->assertNotNull(Invitation::findByToken($token)->accepted_at);
    }

    public function test_an_expired_invitation_cannot_be_used(): void
    {
        $merchant = Merchant::factory()->create();
        $owner = $this->memberOf($merchant, MerchantRole::Owner);
        $token = Invitation::issue($merchant, 'late@example.com', MerchantRole::Staff, $owner);
        Invitation::findByToken($token)->update(['expires_at' => now()->subMinute()]);

        $this->get("/invite/{$token}")->assertNotFound();
        $this->assertFalse(User::where('email', 'late@example.com')->exists());
    }

    public function test_an_invitation_cannot_be_used_twice(): void
    {
        $merchant = Merchant::factory()->create();
        $owner = $this->memberOf($merchant, MerchantRole::Owner);
        $token = Invitation::issue($merchant, 'once@example.com', MerchantRole::Staff, $owner);

        $payload = ['name' => 'Once', 'password' => 'a-strong-pass', 'password_confirmation' => 'a-strong-pass'];
        $this->post("/invite/{$token}", $payload)->assertRedirect();
        $this->post("/invite/{$token}", $payload)->assertNotFound();
    }

    public function test_a_resent_invitation_replaces_the_old_token(): void
    {
        $merchant = Merchant::factory()->create();
        $owner = $this->memberOf($merchant, MerchantRole::Owner);
        $old = Invitation::issue($merchant, 'again@example.com', MerchantRole::Staff, $owner);
        $new = Invitation::issue($merchant, 'again@example.com', MerchantRole::Staff, $owner);

        $this->assertSame(1, Invitation::where('email', 'again@example.com')->count());
        $this->get("/invite/{$old}")->assertNotFound();
        $this->get("/invite/{$new}")->assertOk();
    }
}
