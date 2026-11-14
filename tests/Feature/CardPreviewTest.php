<?php

namespace Tests\Feature;

use App\Enums\MerchantRole;
use App\Filament\Resources\LoyaltyPrograms\LoyaltyProgramResource;
use App\Filament\Resources\LoyaltyPrograms\Pages\ManageLoyaltyPrograms;
use App\Models\Merchant;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class CardPreviewTest extends TestCase
{
    use RefreshDatabase;

    private function ownerIn(Merchant $shop): void
    {
        $owner = User::factory()->create();
        $shop->users()->attach($owner, ['role' => MerchantRole::Owner->value]);
        $this->actingAs($owner);
        Filament::setCurrentPanel('merchant');
        Filament::setTenant($shop);
    }

    public function test_the_preview_draws_what_the_owner_typed(): void
    {
        $shop = Merchant::factory()->create();
        $this->ownerIn($shop);

        $typed = [
            'name' => 'Preview Coffee Card',
            'reward_label' => 'Free latte',
            'stamps_required' => 8,
            'background_color' => '#123456',
        ];

        Livewire::test(ManageLoyaltyPrograms::class)
            ->mountAction('create')
            ->fillForm($typed)
            ->assertActionDataSet(['name' => 'Preview Coffee Card']);

        $data = LoyaltyProgramResource::previewData(fn (string $key) => $typed[$key] ?? null);
        $html = view('filament.loyalty-card-preview', $data)->render();

        $this->assertStringContainsString('Preview Coffee Card', $html);
        $this->assertStringContainsString('Free latte', $html);
        $this->assertStringContainsString('#123456', $html);
        $this->assertStringContainsString('3 / 8', $html);
    }

    public function test_invalid_colours_never_reach_the_preview(): void
    {
        $data = LoyaltyProgramResource::previewData(
            fn (string $key) => match ($key) {
                'background_color' => 'red;"><script>alert(1)</script>',
                'foreground_color' => '#ffffff',
                default => null,
            }
        );

        $this->assertSame('#3B2416', $data['background'], 'Falls back to the default colour.');
        $this->assertSame('#FFFFFF', $data['foreground']);
    }
}
