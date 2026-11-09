<?php

namespace Tests\Feature;

use App\Actions\AddStamp;
use App\Actions\IssueLoyaltyCard;
use App\Actions\RedeemReward;
use App\Enums\CardActivityType;
use App\Exceptions\NoRewardAvailable;
use App\Models\LoyaltyCard;
use App\Models\LoyaltyProgram;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class LoyaltyCardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Http::preventStrayRequests();
    }

    public function test_signing_up_creates_a_customer_a_card_and_an_apple_wallet_pass(): void
    {
        $program = LoyaltyProgram::factory()->create();

        $card = app(IssueLoyaltyCard::class)->handle($program, 'Fatima', '3612 3456');

        $this->assertSame('97336123456', $card->customer->phone);
        $this->assertSame(0, $card->stamps);

        $pass = $card->firstApplePass();
        $this->assertNotNull($pass);
        $this->assertSame('0 / 10', $this->passField($pass->content, 'primaryFields', 'stamps'));
        $this->assertSame($card->code, $pass->content['barcodes'][0]['message']);
    }

    public function test_signing_up_twice_with_the_same_number_returns_the_same_card(): void
    {
        $program = LoyaltyProgram::factory()->create();
        $issue = app(IssueLoyaltyCard::class);

        $first = $issue->handle($program, 'Fatima', '36123456');
        $second = $issue->handle($program, 'Fatima A.', '+973 3612 3456');

        $this->assertTrue($first->is($second));
        $this->assertSame(1, LoyaltyCard::count());
        $this->assertCount(1, $second->mobilePasses);
    }

    public function test_adding_a_stamp_updates_the_card_logs_the_activity_and_refreshes_the_pass(): void
    {
        $staff = User::factory()->create();
        $card = app(IssueLoyaltyCard::class)->handle(LoyaltyProgram::factory()->create(), 'Ali', '36000001');

        $card = app(AddStamp::class)->handle($card, $staff);

        $this->assertSame(1, $card->stamps);
        $this->assertSame(1, $card->lifetime_stamps);
        $this->assertNotNull($card->last_visit_at);
        $this->assertDatabaseHas('card_activities', [
            'loyalty_card_id' => $card->id,
            'user_id' => $staff->id,
            'type' => CardActivityType::Stamp->value,
        ]);
        $this->assertSame('1 / 10', $this->passField($card->firstApplePass()->content, 'primaryFields', 'stamps'));
    }

    public function test_filling_the_card_unlocks_a_reward_and_carries_extra_stamps_over(): void
    {
        $program = LoyaltyProgram::factory()->stampsRequired(5)->create();
        $card = app(IssueLoyaltyCard::class)->handle($program, 'Ali', '36000002');
        $card->update(['stamps' => 4]);

        $card = app(AddStamp::class)->handle($card, User::factory()->create(), quantity: 3);

        $this->assertSame(2, $card->stamps);
        $this->assertSame(1, $card->rewards_available);
        $pass = $card->firstApplePass();
        $this->assertSame('2 / 5', $this->passField($pass->content, 'primaryFields', 'stamps'));
        $this->assertSame('1', $this->passField($pass->content, 'headerFields', 'rewards'));
    }

    public function test_stamp_quantity_is_limited_per_scan(): void
    {
        $card = LoyaltyCard::factory()->create();

        $this->expectException(\InvalidArgumentException::class);

        app(AddStamp::class)->handle($card, User::factory()->create(), quantity: AddStamp::MAX_STAMPS_PER_SCAN + 1);
    }

    public function test_redeeming_uses_one_reward(): void
    {
        $staff = User::factory()->create();
        $card = app(IssueLoyaltyCard::class)->handle(LoyaltyProgram::factory()->create(), 'Ali', '36000003');
        $card->update(['rewards_available' => 2]);

        $card = app(RedeemReward::class)->handle($card, $staff);

        $this->assertSame(1, $card->rewards_available);
        $this->assertSame(1, $card->rewards_redeemed);
        $this->assertDatabaseHas('card_activities', [
            'loyalty_card_id' => $card->id,
            'type' => CardActivityType::Redeem->value,
        ]);
    }

    public function test_redeeming_without_a_reward_is_refused(): void
    {
        $card = LoyaltyCard::factory()->create();

        $this->expectException(NoRewardAvailable::class);

        app(RedeemReward::class)->handle($card, User::factory()->create());
    }

    /**
     * @param  array<string, mixed>  $content
     */
    private function passField(array $content, string $zone, string $key): ?string
    {
        $field = collect($content['storeCard'][$zone] ?? [])->firstWhere('key', $key);

        return $field['value'] ?? null;
    }
}
