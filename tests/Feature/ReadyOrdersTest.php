<?php

namespace Tests\Feature;

use App\Enums\MerchantRole;
use App\Enums\OrderStatus;
use App\Filament\Resources\Orders\Pages\ManageOrders;
use App\Models\LoyaltyCard;
use App\Models\Merchant;
use App\Models\Order;
use App\Models\Plan;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ReadyOrdersTest extends TestCase
{
    use RefreshDatabase;

    private function shop(): Merchant
    {
        $plan = Plan::factory()->create(['features' => ['loyalty_cards', 'cashier_scan', 'order_queue']]);

        return Merchant::factory()->create(['plan_id' => $plan->id]);
    }

    private function actingInPanel(User $user, Merchant $merchant): void
    {
        $this->actingAs($user);
        Filament::setCurrentPanel('merchant');
        Filament::setTenant($merchant);
    }

    public function test_staff_can_place_an_order_for_a_card_by_code(): void
    {
        $shop = $this->shop();
        $staff = User::factory()->create();
        $shop->users()->attach($staff, ['role' => MerchantRole::Staff->value]);
        $card = LoyaltyCard::factory()->create(['merchant_id' => $shop->id]);
        $this->actingInPanel($staff, $shop);

        Livewire::test(ManageOrders::class)
            ->callAction('newOrder', ['lookup' => $card->code, 'note' => 'Latte, extra hot'])
            ->assertHasNoActionErrors();

        $order = Order::sole();
        $this->assertSame($card->id, $order->loyalty_card_id);
        $this->assertSame(1, $order->number);
        $this->assertSame(OrderStatus::Preparing, $order->status);
        $this->assertSame('Latte, extra hot', $order->note);
    }

    public function test_an_unknown_card_does_not_create_an_order(): void
    {
        $shop = $this->shop();
        $staff = User::factory()->create();
        $shop->users()->attach($staff, ['role' => MerchantRole::Staff->value]);
        $this->actingInPanel($staff, $shop);

        Livewire::test(ManageOrders::class)
            ->callAction('newOrder', ['lookup' => 'NOPE1234NOPE'])
            ->assertHasNoActionErrors();

        $this->assertSame(0, Order::count());
    }

    public function test_an_order_moves_from_preparing_to_ready_to_collected(): void
    {
        $shop = $this->shop();
        $staff = User::factory()->create();
        $shop->users()->attach($staff, ['role' => MerchantRole::Staff->value]);
        $order = Order::factory()->create(['merchant_id' => $shop->id, 'status' => OrderStatus::Preparing]);
        $this->actingInPanel($staff, $shop);

        Livewire::test(ManageOrders::class)->callTableAction('markReady', $order);
        $this->assertSame(OrderStatus::Ready, $order->fresh()->status);

        Livewire::test(ManageOrders::class)->callTableAction('markCollected', $order);
        $this->assertSame(OrderStatus::Collected, $order->fresh()->status);
    }
}
