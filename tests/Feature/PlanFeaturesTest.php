<?php

namespace Tests\Feature;

use App\Enums\MerchantRole;
use App\Filament\Pages\ScanCard;
use App\Filament\Resources\Orders\OrderResource;
use App\Models\Merchant;
use App\Models\Plan;
use App\Models\User;
use App\Support\PlanFeatures;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PlanFeaturesTest extends TestCase
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

    private function shopWithFeatures(array $features): Merchant
    {
        $plan = Plan::factory()->create(['features' => $features]);

        return Merchant::factory()->create(['plan_id' => $plan->id]);
    }

    public function test_a_feature_missing_from_the_plan_hides_its_page(): void
    {
        $shop = $this->shopWithFeatures(['order_queue']);
        $this->actingInPanel($this->memberOf($shop, MerchantRole::Owner), $shop);

        $this->assertFalse(ScanCard::canAccess(), 'The till needs loyalty cards, which this plan lacks.');
        $this->assertTrue(OrderResource::canAccess());
    }

    public function test_a_feature_in_the_plan_shows_its_page(): void
    {
        $shop = $this->shopWithFeatures(['loyalty_cards', 'order_queue']);
        $this->actingInPanel($this->memberOf($shop, MerchantRole::Staff), $shop);

        $this->assertTrue(OrderResource::canAccess());
        $this->assertTrue(ScanCard::canAccess());
    }

    public function test_a_shop_without_a_plan_gets_no_plan_features(): void
    {
        $shop = Merchant::factory()->withoutPlan()->create();
        $this->actingInPanel($this->memberOf($shop, MerchantRole::Owner), $shop);

        $this->assertFalse(OrderResource::canAccess());
        $this->assertFalse(ScanCard::canAccess());
    }

    public function test_the_feature_keys_are_the_ones_the_plans_can_sell(): void
    {
        $this->assertContains('order_queue', PlanFeatures::keys());
        $this->assertNotContains('cashier_scan', PlanFeatures::keys(), 'Till access is part of loyalty cards now.');
    }
}
