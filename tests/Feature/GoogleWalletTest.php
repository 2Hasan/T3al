<?php

namespace Tests\Feature;

use App\Actions\AddStamp;
use App\Actions\IssueLoyaltyCard;
use App\Models\LoyaltyCard;
use App\Models\LoyaltyProgram;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class GoogleWalletTest extends TestCase
{
    use RefreshDatabase;

    private const ISSUER_ID = '3388000000000000001';

    protected function setUp(): void
    {
        parent::setUp();

        $privateKey = '';
        openssl_pkey_export(openssl_pkey_new(['private_key_bits' => 2048]), $privateKey);

        config([
            'mobile-pass.google.issuer_id' => self::ISSUER_ID,
            'mobile-pass.google.service_account_key' => json_encode([
                'type' => 'service_account',
                'client_email' => 'wallet@test-project.iam.gserviceaccount.com',
                'private_key' => $privateKey,
            ]),
            'services.google_wallet.logo_url' => 'https://cdn.example.com/logo.png',
        ]);

        cache()->put('mobile-pass.google.access-token', 'test-token', 3600);
        Http::preventStrayRequests();
        Http::fake(['walletobjects.googleapis.com/*' => Http::response(['id' => 'ok'])]);
    }

    public function test_the_card_page_shows_the_google_button_only_when_google_is_configured(): void
    {
        $card = LoyaltyCard::factory()->create();

        $this->get(route('cards.show', $card))->assertSee(route('cards.google', $card));

        config(['mobile-pass.google.issuer_id' => null]);

        $this->get(route('cards.show', $card))->assertDontSee(route('cards.google', $card));
        $this->get(route('cards.google', $card))->assertNotFound();
    }

    public function test_adding_to_google_wallet_creates_the_program_class_and_the_customers_pass(): void
    {
        $program = LoyaltyProgram::factory()->create(['name' => 'بطاقة القهوة', 'background_color' => '#3B2416']);
        $card = LoyaltyCard::factory()->for($program, 'loyaltyProgram')->withStamps(3)->create(['merchant_id' => $program->merchant_id]);

        $this->get(route('cards.google', $card))
            ->assertRedirectContains('https://pay.google.com/gp/v/save/');

        $classId = self::ISSUER_ID.'.testing-program-'.$program->id;
        Http::assertSent(fn (Request $request) => str_ends_with($request->url(), '/loyaltyClass')
            && $request['id'] === $classId
            && $request['programName'] === 'بطاقة القهوة'
            && $request['programLogo']['sourceUri']['uri'] === 'https://cdn.example.com/logo.png'
            && $request['hexBackgroundColor'] === '#3B2416');
        Http::assertSent(fn (Request $request) => str_ends_with($request->url(), '/loyaltyObject')
            && $request['classId'] === $classId
            && $request['accountId'] === $card->code
            && $request['loyaltyPoints']['balance']['string'] === '3 / 10'
            && $request['barcode']['value'] === $card->code);

        $this->assertNotNull($card->firstGooglePass());
        $this->assertNotNull($program->refresh()->google_class_synced_at);
    }

    public function test_opening_the_google_link_twice_reuses_the_same_pass(): void
    {
        $card = LoyaltyCard::factory()->create();

        $this->get(route('cards.google', $card));
        $this->get(route('cards.google', $card));

        $this->assertCount(1, $card->googlePasses);
        Http::assertSentCount(2);
    }

    public function test_stamping_pushes_the_new_balance_to_the_customers_google_pass(): void
    {
        $card = app(IssueLoyaltyCard::class)->handle(LoyaltyProgram::factory()->create(), 'Ali', '36000001');
        $this->get(route('cards.google', $card));

        app(AddStamp::class)->handle($card, User::factory()->create());

        Http::assertSent(fn (Request $request) => $request->method() === 'PATCH'
            && str_contains($request->url(), '/loyaltyObject/')
            && $request['loyaltyPoints']['balance']['string'] === '1 / 10'
            && $request['secondaryLoyaltyPoints']['balance']['string'] === '0');
    }

    public function test_editing_the_program_resyncs_the_google_class_on_the_next_update(): void
    {
        $card = app(IssueLoyaltyCard::class)->handle(LoyaltyProgram::factory()->create(), 'Ali', '36000002');
        $this->get(route('cards.google', $card));

        $this->travel(1)->minute();
        $card->loyaltyProgram->update(['name' => 'بطاقة جديدة']);
        app(AddStamp::class)->handle($card, User::factory()->create());

        Http::assertSent(fn (Request $request) => str_ends_with($request->url(), '/loyaltyClass')
            && $request['programName'] === 'بطاقة جديدة');
    }

    public function test_a_google_outage_does_not_undo_a_stamp(): void
    {
        $card = app(IssueLoyaltyCard::class)->handle(LoyaltyProgram::factory()->create(), 'Ali', '36000003');
        $this->get(route('cards.google', $card));
        Http::fake(['walletobjects.googleapis.com/*' => Http::response(['error' => 'down'], 500)]);

        $card = app(AddStamp::class)->handle($card, User::factory()->create());

        $this->assertSame(1, $card->refresh()->stamps);
    }

    public function test_cards_issued_without_google_never_call_the_google_api(): void
    {
        $card = app(IssueLoyaltyCard::class)->handle(LoyaltyProgram::factory()->create(), 'Ali', '36000004');

        app(AddStamp::class)->handle($card, User::factory()->create());

        Http::assertNothingSent();
    }
}
