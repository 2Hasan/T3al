<?php

namespace Tests\Feature;

use App\Enums\MerchantRole;
use App\Filament\Pages\ScanCard;
use App\Filament\Pages\Tenancy\RegisterMerchant;
use App\Filament\Resources\LoyaltyPrograms\LoyaltyProgramResource;
use App\Models\LoyaltyCard;
use App\Models\LoyaltyProgram;
use App\Models\Merchant;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class MerchantPanelTest extends TestCase
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

    public function test_registering_a_shop_makes_the_user_its_owner_with_a_starter_card(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        Filament::setCurrentPanel('merchant');

        Livewire::test(RegisterMerchant::class)
            ->fillForm(['name' => 'Bean Bar', 'reward_label' => 'قهوة مجانية'])
            ->call('register')
            ->assertHasNoFormErrors();

        $merchant = Merchant::sole();
        $this->assertTrue($user->isOwnerOf($merchant));
        $this->assertSame('قهوة مجانية', $merchant->activeProgram()->reward_label);
    }

    public function test_a_user_cannot_open_another_merchants_panel(): void
    {
        $mine = Merchant::factory()->create();
        $theirs = Merchant::factory()->create();
        $user = $this->memberOf($mine, MerchantRole::Owner);

        $this->actingAs($user)->get("/app/{$mine->slug}/scan-card")->assertOk();
        $this->actingAs($user)->get("/app/{$theirs->slug}/scan-card")->assertNotFound();
    }

    public function test_cashiers_cannot_manage_loyalty_programs(): void
    {
        $merchant = Merchant::factory()->create();

        $this->actingInPanel($this->memberOf($merchant, MerchantRole::Staff), $merchant);
        $this->assertFalse(LoyaltyProgramResource::canAccess());

        $this->actingInPanel($this->memberOf($merchant, MerchantRole::Owner), $merchant);
        $this->assertTrue(LoyaltyProgramResource::canAccess());
    }

    public function test_the_cashier_can_find_a_card_and_add_a_stamp(): void
    {
        $card = LoyaltyCard::factory()->create();
        $cashier = $this->memberOf($card->merchant, MerchantRole::Staff);
        $this->actingInPanel($cashier, $card->merchant);

        Livewire::test(ScanCard::class)
            ->set('search', strtolower($card->code))
            ->call('lookup')
            ->assertSet('cardId', $card->id)
            ->assertSee($card->customer->name)
            ->call('addStamp', 1)
            ->assertNotified('تمت إضافة ختم');

        $this->assertSame(1, $card->refresh()->stamps);
        $this->assertDatabaseHas('card_activities', ['loyalty_card_id' => $card->id, 'user_id' => $cashier->id]);
    }

    public function test_the_cashier_can_find_a_card_by_phone_number(): void
    {
        $card = LoyaltyCard::factory()->create();
        $card->customer->update(['phone' => '97336123456']);
        $this->actingInPanel($this->memberOf($card->merchant, MerchantRole::Staff), $card->merchant);

        Livewire::test(ScanCard::class)
            ->set('search', '3612 3456')
            ->call('lookup')
            ->assertSet('cardId', $card->id);
    }

    public function test_the_cashier_cannot_find_another_merchants_card(): void
    {
        $otherCard = LoyaltyCard::factory()->create();
        $merchant = Merchant::factory()->create();
        $this->actingInPanel($this->memberOf($merchant, MerchantRole::Staff), $merchant);

        Livewire::test(ScanCard::class)
            ->set('search', $otherCard->code)
            ->call('lookup')
            ->assertSet('cardId', null)
            ->assertNotified('ما لقينا بطاقة بهذا الرقم');
    }

    public function test_redeeming_without_a_reward_shows_an_error(): void
    {
        $card = LoyaltyCard::factory()->create();
        $this->actingInPanel($this->memberOf($card->merchant, MerchantRole::Staff), $card->merchant);

        Livewire::test(ScanCard::class)
            ->call('lookupScanned', $card->code)
            ->call('redeem')
            ->assertNotified('ما فيه مكافأة متاحة في هذي البطاقة.');

        $this->assertSame(0, $card->refresh()->rewards_redeemed);
    }

    public function test_the_cashier_registers_a_customer_and_gets_the_card_link_to_show_them(): void
    {
        $program = LoyaltyProgram::factory()->create();
        $this->actingInPanel($this->memberOf($program->merchant, MerchantRole::Staff), $program->merchant);

        $page = Livewire::test(ScanCard::class)
            ->callAction('registerCustomer', data: ['name' => 'Zainab', 'phone' => '3655 1234'])
            ->assertHasNoActionErrors()
            ->assertNotified('تم تسجيل العميل')
            ->assertSet('showCardLink', true);

        $card = LoyaltyCard::sole();
        $this->assertSame('97336551234', $card->customer->phone);
        $this->assertSame($program->id, $card->loyalty_program_id);
        $page->assertSet('cardId', $card->id)
            ->assertSee(route('cards.show', $card))
            ->assertSee('https://wa.me/97336551234', false);
    }

    public function test_registering_an_existing_number_reopens_that_customers_card(): void
    {
        $program = LoyaltyProgram::factory()->create();
        $this->actingInPanel($this->memberOf($program->merchant, MerchantRole::Staff), $program->merchant);

        Livewire::test(ScanCard::class)
            ->callAction('registerCustomer', data: ['name' => 'Zainab', 'phone' => '36551234'])
            ->callAction('registerCustomer', data: ['name' => 'Zainab', 'phone' => '+973 3655 1234'])
            ->assertNotified('العميل مسجّل من قبل');

        $this->assertSame(1, LoyaltyCard::count());
    }

    public function test_registration_rejects_an_invalid_phone_number(): void
    {
        $program = LoyaltyProgram::factory()->create();
        $this->actingInPanel($this->memberOf($program->merchant, MerchantRole::Staff), $program->merchant);

        Livewire::test(ScanCard::class)
            ->callAction('registerCustomer', data: ['name' => 'Zainab', 'phone' => '123'])
            ->assertHasActionErrors(['phone']);

        $this->assertSame(0, LoyaltyCard::count());
    }

    public function test_registration_needs_an_active_loyalty_program(): void
    {
        $merchant = Merchant::factory()->create();
        $this->actingInPanel($this->memberOf($merchant, MerchantRole::Staff), $merchant);

        Livewire::test(ScanCard::class)
            ->callAction('registerCustomer', data: ['name' => 'Zainab', 'phone' => '36551234'])
            ->assertNotified('ما فيه بطاقة ولاء مفعّلة');

        $this->assertSame(0, LoyaltyCard::count());
    }
}
