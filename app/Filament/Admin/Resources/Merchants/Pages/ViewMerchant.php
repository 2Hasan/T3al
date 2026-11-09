<?php

namespace App\Filament\Admin\Resources\Merchants\Pages;

use App\Filament\Admin\Resources\Merchants\MerchantResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewMerchant extends ViewRecord
{
    protected static string $resource = MerchantResource::class;

    protected function getHeaderActions(): array
    {
        return [
            MerchantResource::recordPaymentAction(),
            MerchantResource::extendTrialAction(),
            MerchantResource::suspendAction(),
            MerchantResource::activateAction(),
            EditAction::make(),
        ];
    }
}
