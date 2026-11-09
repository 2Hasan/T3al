<?php

namespace Tests\Feature;

use Tests\TestCase;

class MarketingPageTest extends TestCase
{
    public function test_the_home_page_is_in_arabic_by_default_and_right_to_left(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('تعال، ترجّع.', false)
            ->assertSee('dir="rtl"', false);
    }

    public function test_visitors_can_switch_to_english(): void
    {
        $this->get('/locale/en')->assertRedirect();

        $this->get('/')
            ->assertOk()
            ->assertSee('Come back, we', false)
            ->assertSee('dir="ltr"', false);
    }

    public function test_unsupported_locales_are_rejected(): void
    {
        $this->get('/locale/fr')->assertNotFound();
    }
}
