<?php

namespace Tests\Feature;

use App\Models\LoyaltyCard;
use App\Models\Merchant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CardPageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['mobile-pass.apple.certificate_path' => '/path/to/certificate.p12']);
    }

    public function test_the_card_page_offers_only_adding_the_card_to_the_wallet(): void
    {
        $card = LoyaltyCard::factory()->withStamps(3)->create();

        $this->get(route('cards.show', $card))
            ->assertOk()
            ->assertSee($card->customer->name)
            ->assertSee(route('cards.apple', $card))
            ->assertDontSee('<form', false);
    }

    public function test_the_apple_wallet_link_redirects_to_a_signed_pass_download(): void
    {
        $card = LoyaltyCard::factory()->create();

        $this->get(route('cards.apple', $card))->assertRedirectContains('/passkit/v1/apple/');

        $this->assertNotNull($card->firstApplePass());
    }

    public function test_an_unknown_card_code_is_not_found(): void
    {
        $this->get('/c/NOTAREALCODE')->assertNotFound();
    }

    public function test_customers_cannot_sign_themselves_up(): void
    {
        $merchant = Merchant::factory()->create();

        $this->get("/j/{$merchant->slug}")->assertNotFound();
        $this->post("/j/{$merchant->slug}", ['name' => 'X', 'phone' => '36123456'])->assertNotFound();
        $this->assertSame(0, LoyaltyCard::count());
    }

    public function test_the_apple_button_is_hidden_until_the_apple_certificate_is_configured(): void
    {
        config(['mobile-pass.apple.certificate_path' => null]);
        $card = LoyaltyCard::factory()->create();

        $this->get(route('cards.show', $card))
            ->assertDontSee(route('cards.apple', $card))
            ->assertSee('إضافة البطاقة للمحفظة غير متاحة حالياً.');
        $this->get(route('cards.apple', $card))->assertNotFound();
    }
}
