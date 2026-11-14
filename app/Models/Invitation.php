<?php

namespace App\Models;

use App\Enums\MerchantRole;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * An email invitation for someone to join a shop's team. Only the hash of the
 * token is stored; the plain token only travels in the email link.
 */
#[Fillable(['merchant_id', 'invited_by', 'email', 'role', 'token_hash', 'expires_at', 'accepted_at'])]
class Invitation extends Model
{
    use HasFactory;

    public const VALID_DAYS = 7;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'role' => MerchantRole::class,
            'expires_at' => 'datetime',
            'accepted_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Merchant, $this>
     */
    public function merchant(): BelongsTo
    {
        return $this->belongsTo(Merchant::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function inviter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'invited_by');
    }

    public function isUsable(): bool
    {
        return $this->accepted_at === null && $this->expires_at->isFuture();
    }

    /**
     * Creates an invitation and returns the plain token, which is shown once in the email.
     */
    public static function issue(Merchant $merchant, string $email, MerchantRole $role, User $by): string
    {
        $token = Str::random(64);

        static::query()
            ->where('merchant_id', $merchant->id)
            ->where('email', $email)
            ->whereNull('accepted_at')
            ->delete();

        static::create([
            'merchant_id' => $merchant->id,
            'invited_by' => $by->id,
            'email' => $email,
            'role' => $role,
            'token_hash' => hash('sha256', $token),
            'expires_at' => now()->addDays(self::VALID_DAYS),
        ]);

        return $token;
    }

    public static function findByToken(string $token): ?self
    {
        return static::query()->where('token_hash', hash('sha256', $token))->first();
    }
}
