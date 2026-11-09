<?php

namespace Tests\Feature;

use App\Actions\RecordPayment;
use App\Actions\SuspendExpiredMerchants;
use App\Enums\MerchantStatus;
use App\Filament\Admin\Resources\Merchants\Pages\ManageMerchants;
use App\Models\Merchant;
use App\Models\Payment;
use App\Models\Plan;
use App\Models\User;
use Carbon\Carbon;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class BillingTest extends TestCase
{
    use RefreshDatabase;

    private function shopOnPlan(array $attributes = []): Merchant
    {
        $plan = Plan::factory()->create(['price_per_branch' => '3.000']);

        return Merchant::factory()->create(array_merge(['plan_id' => $plan->id], $attributes));
    }

    public function test_the_monthly_amount_is_every_branch_at_the_plan_price(): void
    {
        $merchant = $this->shopOnPlan();
        $merchant->branches()->createMany([['name' => 'A'], ['name' => 'B'], ['name' => 'C']]);

        $this->assertSame('9.000', $merchant->monthlyAmount());
    }

    public function test_a_payment_activates_the_shop_and_extends_it_by_the_months_paid(): void
    {
        $merchant = $this->shopOnPlan(['status' => MerchantStatus::Suspended]);

        app(RecordPayment::class)->handle($merchant, 3, '9.000', Carbon::today(), 'TRF-1', null, null);

        $merchant->refresh();
        $this->assertSame(MerchantStatus::Active, $merchant->status);
        $this->assertTrue($merchant->paid_until->isFuture());
        $this->assertTrue($merchant->paid_until->gte(now()->addMonths(3)->subMinute()));
        $this->assertSame(1, Payment::count());
    }

    public function test_paying_early_stacks_the_months_on_the_current_end_date(): void
    {
        $merchant = $this->shopOnPlan(['status' => MerchantStatus::Active, 'paid_until' => now()->addMonth()]);

        app(RecordPayment::class)->handle($merchant, 1, '3.000', Carbon::today(), null, null, null);

        $this->assertTrue($merchant->refresh()->paid_until->gte(now()->addMonths(2)->subMinute()));
    }

    public function test_expired_trials_and_paid_periods_are_suspended(): void
    {
        $expiredTrial = $this->shopOnPlan(['status' => MerchantStatus::Trial, 'trial_ends_at' => now()->subDay()]);
        $expiredPaid = $this->shopOnPlan(['status' => MerchantStatus::Active, 'paid_until' => now()->subDay()]);
        $runningTrial = $this->shopOnPlan(['status' => MerchantStatus::Trial, 'trial_ends_at' => now()->addDay()]);
        $runningPaid = $this->shopOnPlan(['status' => MerchantStatus::Active, 'paid_until' => now()->addDay()]);

        $count = app(SuspendExpiredMerchants::class)->handle();

        $this->assertSame(2, $count);
        $this->assertSame(MerchantStatus::Suspended, $expiredTrial->refresh()->status);
        $this->assertSame(MerchantStatus::Suspended, $expiredPaid->refresh()->status);
        $this->assertSame(MerchantStatus::Trial, $runningTrial->refresh()->status);
        $this->assertSame(MerchantStatus::Active, $runningPaid->refresh()->status);
    }

    public function test_a_suspended_shop_cannot_open_the_cashier_screen(): void
    {
        $merchant = $this->shopOnPlan(['status' => MerchantStatus::Suspended]);
        $owner = User::factory()->create();
        $merchant->users()->attach($owner, ['role' => 'owner']);

        $this->actingAs($owner)->get("/app/{$merchant->slug}/scan-card")->assertForbidden();
    }

    public function test_the_admin_records_a_bank_transfer_from_the_shops_page(): void
    {
        $merchant = $this->shopOnPlan(['status' => MerchantStatus::Suspended]);
        $admin = User::factory()->create(['is_admin' => true]);
        $this->actingAs($admin);
        Filament::setCurrentPanel(Filament::getPanel('admin'));

        Livewire::test(ManageMerchants::class)
            ->callTableAction('recordPayment', $merchant, [
                'months' => 1,
                'amount' => '3.000',
                'paid_on' => now()->toDateTimeString(),
                'reference' => 'TRF-42',
            ])
            ->assertHasNoTableActionErrors();

        $merchant->refresh();
        $this->assertSame(MerchantStatus::Active, $merchant->status);
        $this->assertDatabaseHas('payments', ['merchant_id' => $merchant->id, 'reference' => 'TRF-42', 'recorded_by' => $admin->id]);
    }
}
