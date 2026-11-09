<?php

namespace Tests\Feature;

use App\Enums\MerchantStatus;
use App\Filament\Admin\Resources\Merchants\MerchantResource;
use App\Filament\Admin\Resources\Merchants\Pages\ViewMerchant;
use App\Filament\Admin\Widgets\PlatformStats;
use App\Models\Merchant;
use App\Models\Plan;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AdminExperienceTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['is_admin' => true]);
    }

    private function shopOnPlan(MerchantStatus $status, int $branches): Merchant
    {
        $plan = Plan::factory()->create(['price_per_branch' => '3.000']);
        $shop = Merchant::factory()->create([
            'plan_id' => $plan->id,
            'status' => $status,
            'paid_until' => now()->addMonth(),
        ]);
        $shop->branches()->createMany(array_map(fn (int $i) => ['name' => "Branch {$i}"], range(1, $branches)));

        return $shop;
    }

    public function test_the_dashboard_totals_the_monthly_revenue_from_active_shops_only(): void
    {
        $this->shopOnPlan(MerchantStatus::Active, 2);   // 6.000
        $this->shopOnPlan(MerchantStatus::Active, 1);   // 3.000
        $this->shopOnPlan(MerchantStatus::Trial, 5);    // not billed yet

        $this->actingAs($this->admin());
        Filament::setCurrentPanel(Filament::getPanel('admin'));

        Livewire::test(PlatformStats::class)
            ->assertSee('9.000 BHD');
    }

    public function test_the_shop_page_opens_with_billing_details(): void
    {
        $shop = $this->shopOnPlan(MerchantStatus::Active, 2);
        $admin = $this->admin();

        $this->actingAs($admin)
            ->get("/admin/merchants/{$shop->id}")
            ->assertOk()
            ->assertSee($shop->name)
            ->assertSee('6.000 BHD');
    }

    public function test_the_shops_list_marks_shops_ending_within_a_week(): void
    {
        $shop = $this->shopOnPlan(MerchantStatus::Active, 1);
        $shop->update(['paid_until' => now()->addDays(3)]);

        $this->assertSame(1, Merchant::query()->endingWithin(7)->count());
        $this->assertSame('3 يوم متبقي', MerchantResource::daysLabel($shop->refresh()));
    }

    public function test_the_shop_page_can_record_a_payment_with_a_notification(): void
    {
        $shop = $this->shopOnPlan(MerchantStatus::Suspended, 1);
        $this->actingAs($this->admin());
        Filament::setCurrentPanel(Filament::getPanel('admin'));

        Livewire::test(ViewMerchant::class, ['record' => $shop->getRouteKey()])
            ->callAction('recordPayment', data: [
                'months' => 1,
                'amount' => '3.000',
                'paid_on' => now()->toDateTimeString(),
                'reference' => 'TRF-9',
            ])
            ->assertHasNoActionErrors()
            ->assertNotified();

        $this->assertSame(MerchantStatus::Active, $shop->refresh()->status);
    }
}
