<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ModeratorGrant extends Model
{
    protected $fillable = ['user_id', 'role', 'locale', 'is_active', 'granted_by_user_id', 'changed_by_user_id', 'reason', 'revoked_at'];

    public static function activeForLocale(User $user, string $locale): Builder
    {
        return self::query()->where('user_id', $user->id)
            ->where('is_active', true)->whereNull('revoked_at')
            ->where(function (Builder $query) use ($locale): void {
                $query->where(fn (Builder $moderator) => $moderator->where('role', 'moderator')->where('locale', $locale))
                    ->orWhere(fn (Builder $admin) => $admin->where('role', 'admin')->where('locale', '*'));
            })->orderByRaw("CASE WHEN role = 'moderator' THEN 0 ELSE 1 END");
    }

    protected function casts(): array
    {
        return ['is_active' => 'boolean', 'revoked_at' => 'immutable_datetime'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
