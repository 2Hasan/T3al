<?php

namespace App\Filament\Admin\Pages;

use App\Models\AppSetting;
use App\Support\MailSettings as ApplyMailSettings;
use BackedEnum;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Mail;
use Throwable;

/**
 * SMTP settings for every email the platform sends (team invitations, receipts).
 * Saved here, so the server's .env does not have to change.
 */
class MailSettings extends Page
{
    protected string $view = 'filament.admin.pages.mail-settings';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedEnvelope;

    protected static ?int $navigationSort = 10;

    /** @var array<string, mixed> */
    public ?array $data = [];

    public static function getNavigationLabel(): string
    {
        return __('app.mail_settings');
    }

    public function getTitle(): string
    {
        return __('app.mail_settings');
    }

    public function mount(): void
    {
        $this->form->fill(array_merge([
            'port' => 587,
            'encryption' => 'tls',
            'from_name' => 'T3al',
        ], AppSetting::mail(), ['password' => '']));
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('host')
                    ->label(__('app.mail_host'))
                    ->required()
                    ->maxLength(190),
                TextInput::make('port')
                    ->label(__('app.mail_port'))
                    ->numeric()
                    ->required(),
                Select::make('encryption')
                    ->label(__('app.mail_encryption'))
                    ->options(['tls' => 'TLS', 'ssl' => 'SSL', 'none' => __('app.none')])
                    ->required(),
                TextInput::make('username')
                    ->label(__('app.mail_username'))
                    ->maxLength(190),
                TextInput::make('password')
                    ->label(__('app.mail_password'))
                    ->password()
                    ->revealable()
                    ->helperText(__('app.mail_password_help')),
                TextInput::make('from_address')
                    ->label(__('app.mail_from_address'))
                    ->email()
                    ->required(),
                TextInput::make('from_name')
                    ->label(__('app.mail_from_name'))
                    ->required()
                    ->maxLength(100),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $state = $this->form->getState();

        // An empty password field keeps the stored password.
        if (blank($state['password'] ?? null)) {
            $state['password'] = AppSetting::mail()['password'] ?? '';
        }

        AppSetting::storeMail($state);
        ApplyMailSettings::apply();

        Notification::make()->title(__('app.mail_saved'))->success()->send();
    }

    public function sendTest(): void
    {
        $email = $this->form->getState()['from_address'] ?? null;

        // Use the saved settings, not whatever is typed but not yet saved.
        ApplyMailSettings::apply();

        try {
            Mail::raw(__('app.mail_test_body'), function ($message) use ($email): void {
                $message->to($email)->subject(__('app.mail_test_subject'));
            });

            Notification::make()->title(__('app.mail_test_sent'))->success()->send();
        } catch (Throwable $e) {
            Notification::make()->title(__('app.mail_test_failed'))->body($e->getMessage())->danger()->send();
        }
    }
}
