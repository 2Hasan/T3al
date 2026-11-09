<?php

namespace Tests\Feature;

use App\Models\LoyaltyCard;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EnglishLocaleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['mobile-pass.apple.certificate_path' => '/path/to/certificate.p12']);
    }

    public function test_the_customer_card_page_renders_in_english(): void
    {
        $card = LoyaltyCard::factory()->withStamps(3)->create();

        $this->get('/locale/en');

        $this->get(route('cards.show', $card))
            ->assertOk()
            ->assertSee('Collect 10 stamps and get', false)
            ->assertSee('Add to Apple Wallet', false)
            ->assertDontSee('أضف إلى Apple Wallet', false);
    }

    public function test_the_card_page_is_left_to_right_in_english(): void
    {
        $card = LoyaltyCard::factory()->create();

        $this->get('/locale/en');

        $this->get(route('cards.show', $card))
            ->assertSee('dir="ltr"', false)
            ->assertSee('lang="en"', false);
    }

    public function test_the_card_page_stays_in_arabic_by_default(): void
    {
        $card = LoyaltyCard::factory()->create();

        $this->get(route('cards.show', $card))
            ->assertOk()
            ->assertSee('أضف إلى Apple Wallet', false);
    }

    public function test_every_translation_key_exists_in_both_languages(): void
    {
        $arabic = array_keys(require lang_path('ar/app.php'));
        $english = array_keys(require lang_path('en/app.php'));

        $this->assertSame([], array_diff($arabic, $english));
        $this->assertSame([], array_diff($english, $arabic));
    }
}
