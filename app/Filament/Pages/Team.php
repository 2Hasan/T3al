<?php

namespace App\Filament\Pages;

use App\Enums\MerchantRole;
use App\Mail\TeamInvitation;
use App\Models\Invitation;
use App\Models\Merchant;
use App\Models\User;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Mail;
use Throwable;

/**
 * Team management for the shop owner: invite staff by email, see who has access,
 * resend or cancel pending invitations, and remove a staff member.
 */
class Team extends Page
{
    protected string $view = 'filament.pages.team';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUserGroup;

    protected static ?int $navigationSort = 5;

    public static function canAccess(): bool
    {
        $shop = Filament::getTenant();
        $user = Filament::auth()->user();

        return $shop instanceof Merchant
            && $shop->isOperational()
            && $user instanceof User
            && $user->isOwnerOf($shop);
    }

    public static function getNavigationLabel(): string
    {
        return __('app.team');
    }

    public function getTitle(): string
    {
        return __('app.team');
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('invite')
                ->label(__('app.team_invite'))
                ->icon(Heroicon::OutlinedUserPlus)
                ->modalHeading(__('app.team_invite_heading'))
                ->modalSubmitActionLabel(__('app.team_send_invite'))
                ->schema([
                    TextInput::make('email')
                        ->label(__('app.team_email'))
                        ->email()
                        ->required()
                        ->maxLength(190),
                ])
                ->action(function (array $data): void {
                    $this->sendInvitation(strtolower(trim($data['email'])));
                }),
        ];
    }

    public function resend(int $invitationId): void
    {
        $invitation = $this->pendingInvitations()->whereKey($invitationId)->first();

        if ($invitation === null) {
            return;
        }

        $this->sendInvitation($invitation->email);
    }

    public function revoke(int $invitationId): void
    {
        $this->pendingInvitations()->whereKey($invitationId)->delete();

        Notification::make()->title(__('app.team_invite_revoked'))->success()->send();
    }

    public function removeMember(int $userId): void
    {
        $shop = $this->shop();
        $member = $shop->users()->whereKey($userId)->first();

        if ($member === null || $member->is(Filament::auth()->user())) {
            return;
        }

        // Owners are not removed here. Only staff can be taken off the team.
        if ($shop->roleOf($member) === MerchantRole::Owner) {
            return;
        }

        $shop->users()->detach($member->id);

        Notification::make()->title(__('app.team_member_removed'))->success()->send();
    }

    /**
     * @return array<int, array{id: int, name: string, email: string, role: string, is_me: bool}>
     */
    public function members(): array
    {
        $me = Filament::auth()->user();

        return $this->shop()->users()->orderByPivot('role')->get()
            ->map(fn (User $user): array => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => MerchantRole::from($user->pivot->role)->label(),
                'is_me' => $user->is($me),
                'can_remove' => $user->pivot->role === MerchantRole::Staff->value,
            ])
            ->all();
    }

    /**
     * @return Collection<int, Invitation>
     */
    public function pendingInvitations()
    {
        return Invitation::query()
            ->where('merchant_id', $this->shop()->id)
            ->whereNull('accepted_at')
            ->where('expires_at', '>', now())
            ->latest('id')
            ->get();
    }

    protected function sendInvitation(string $email): void
    {
        $shop = $this->shop();

        if ($shop->users()->where('email', $email)->exists()) {
            Notification::make()->title(__('app.team_already_member'))->warning()->send();

            return;
        }

        $token = Invitation::issue($shop, $email, MerchantRole::Staff, Filament::auth()->user());
        $invitation = Invitation::findByToken($token);

        try {
            Mail::to($email)->send(new TeamInvitation($invitation, url('/invite/'.$token)));

            Notification::make()->title(__('app.team_invite_sent', ['email' => $email]))->success()->send();
        } catch (Throwable $e) {
            Notification::make()
                ->title(__('app.team_invite_failed'))
                ->body(__('app.team_invite_failed_help'))
                ->danger()
                ->send();

            report($e);
        }
    }

    protected function shop(): Merchant
    {
        /** @var Merchant */
        return Filament::getTenant();
    }
}
