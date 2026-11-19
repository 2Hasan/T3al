<?php

namespace Tests\Feature;

use App\Actions\AddStamp;
use App\Enums\MerchantRole;
use App\Enums\OfferType;
use App\Filament\Pages\Analytics;
use App\Models\Customer;
use App\Models\LoyaltyCard;
use App\Models\LoyaltyProgram;
use App\Models\Merchant;
use App\Models\Offer;
use App\Models\Plan;
use App\Models\User;
use App\Support\ShopAnalytics;
use Carbon\CarbonImmutable;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OffersAndAnalyticsTest extends TestCase
{
    use RefreshDatabase;

    private const TZ = 'Asia/Bahrain';

    private function shop(array $features = ['loyalty_cards', 'smart_offers', 'analytics']): Merchant
    {
        $plan = Plan::factory()->create(['features' => $features]);

        return Merchant::factory()->create(['plan_id' => $plan->id, 'timezone' => self::TZ]);
    }

    private function card(Merchant $shop, array $customer = [], array $card = []): LoyaltyCard
    {
        $program = LoyaltyProgram::factory()->create(['merchant_id' => $shop->id, 'stamps_required' => 10]);
        $customer = Customer::factory()->create(array_merge(['merchant_id' => $shop->id], $customer));

        return LoyaltyCard::factory()->create(array_merge([
            'merchant_id' => $shop->id,
            'loyalty_program_id' => $program->id,
            'customer_id' => $customer->id,
            'stamps' => 0,
        ], $card));
    }

    private function staff(Merchant $shop): User
    {
        $user = User::factory()->create();
        $shop->users()->attach($user, ['role' => MerchantRole::Staff->value]);

        return $user;
    }

    private function today(): CarbonImmutable
    {
        return CarbonImmutable::now(self::TZ);
    }

    public function test_a_multiplier_on_todays_weekday_doubles_the_stamp(): void
    {
        $shop = $this->shop();
        Offer::create(['merchant_id' => $shop->id, 'name' => 'Double today', 'type' => OfferType::Multiplier, 'multiplier' => 2, 'weekdays' => [$this->today()->dayOfWeek], 'bonus_stamps' => 1, 'is_active' => true]);
        $card = $this->card($shop);

        $card = app(AddStamp::class)->handle($card, $this->staff($shop), 1);

        $this->assertSame(2, $card->stamps);
    }

    public function test_a_multiplier_does_not_apply_on_other_weekdays(): void
    {
        $shop = $this->shop();
        Offer::create(['merchant_id' => $shop->id, 'name' => 'Other day', 'type' => OfferType::Multiplier, 'multiplier' => 3, 'weekdays' => [($this->today()->dayOfWeek + 1) % 7], 'bonus_stamps' => 1, 'is_active' => true]);
        $card = $this->card($shop);

        $card = app(AddStamp::class)->handle($card, $this->staff($shop), 1);

        $this->assertSame(1, $card->stamps);
    }

    public function test_a_customer_who_has_been_away_gets_bonus_stamps_on_return(): void
    {
        $shop = $this->shop();
        Offer::create(['merchant_id' => $shop->id, 'name' => 'Come back', 'type' => OfferType::WinBack, 'inactive_days' => 30, 'bonus_stamps' => 2, 'is_active' => true]);
        $card = $this->card($shop, card: ['last_visit_at' => now()->subDays(40)]);

        $card = app(AddStamp::class)->handle($card, $this->staff($shop), 1);

        $this->assertSame(3, $card->stamps);
    }

    public function test_a_recent_visitor_gets_no_win_back_bonus(): void
    {
        $shop = $this->shop();
        Offer::create(['merchant_id' => $shop->id, 'name' => 'Come back', 'type' => OfferType::WinBack, 'inactive_days' => 30, 'bonus_stamps' => 2, 'is_active' => true]);
        $card = $this->card($shop, card: ['last_visit_at' => now()->subDays(3)]);

        $card = app(AddStamp::class)->handle($card, $this->staff($shop), 1);

        $this->assertSame(1, $card->stamps);
    }

    public function test_the_birthday_bonus_is_given_once_a_year(): void
    {
        $shop = $this->shop();
        Offer::create(['merchant_id' => $shop->id, 'name' => 'Birthday', 'type' => OfferType::Birthday, 'bonus_stamps' => 2, 'is_active' => true]);
        $today = $this->today();
        $card = $this->card($shop, customer: ['birthday' => $today->subYears(30)->toDateString()]);
        $staff = $this->staff($shop);

        $card = app(AddStamp::class)->handle($card, $staff, 1);
        $this->assertSame(3, $card->stamps);
        $this->assertNotNull($card->birthday_bonus_at);

        $card = app(AddStamp::class)->handle($card, $staff, 1);
        $this->assertSame(4, $card->stamps, 'The second visit the same year gets no birthday bonus.');
    }

    public function test_offers_do_nothing_without_the_smart_offers_feature(): void
    {
        $shop = $this->shop(['loyalty_cards']);
        Offer::create(['merchant_id' => $shop->id, 'name' => 'Double always', 'type' => OfferType::Multiplier, 'multiplier' => 2, 'bonus_stamps' => 1, 'is_active' => true]);
        $card = $this->card($shop);

        $card = app(AddStamp::class)->handle($card, $this->staff($shop), 1);

        $this->assertSame(1, $card->stamps);
    }

    public function test_inactive_offers_are_ignored(): void
    {
        $shop = $this->shop();
        Offer::create(['merchant_id' => $shop->id, 'name' => 'Off', 'type' => OfferType::Multiplier, 'multiplier' => 3, 'bonus_stamps' => 1, 'is_active' => false]);
        $card = $this->card($shop);

        $card = app(AddStamp::class)->handle($card, $this->staff($shop), 1);

        $this->assertSame(1, $card->stamps);
    }

    public function test_the_analytics_page_is_for_owners_and_counts_the_stamps(): void
    {
        $shop = $this->shop();
        $owner = User::factory()->create();
        $shop->users()->attach($owner, ['role' => MerchantRole::Owner->value]);
        $staff = $this->staff($shop);
        $card = $this->card($shop);
        app(AddStamp::class)->handle($card, $staff, 2);

        $this->actingAs($owner);
        Filament::setCurrentPanel('merchant');
        Filament::setTenant($shop);
        $this->assertTrue(Analytics::canAccess());

        $figures = ShopAnalytics::for($shop);
        $this->assertSame(2, $figures['stamps']);
        $this->assertSame(1, $figures['visits']);
        $this->assertSame(1, $figures['active_customers']);
        $this->assertSame(14, count($figures['daily']));
        $this->assertSame(7, count($figures['weekdays']));

        $this->actingAs($staff);
        Filament::setTenant($shop);
        $this->assertFalse(Analytics::canAccess(), 'Staff do not see shop analytics.');
    }
}
