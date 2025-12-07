<?php

namespace LetoceilingCoder\Media\Models\Traits;

use LetoceilingCoder\Media\Models\Scopes\UserScope;
use Illuminate\Database\Eloquent\Builder;

trait HasUserScope
{
    /**
     * The "booted" method of the model.
     */
    protected static function bootHasUserScope(): void
    {
        if (config('media.user_scoping', true)) {
            static::addGlobalScope(new UserScope);
        }
    }

    /**
     * Получить все записи без учета user_id (обход scope)
     */
    public static function withoutUserScope(): Builder
    {
        return static::withoutGlobalScope(UserScope::class);
    }

    /**
     * Получить записи конкретного пользователя
     */
    public static function forUser($userId): Builder
    {
        return static::withoutGlobalScope(UserScope::class)
            ->where(static::getTableName() . '.user_id', $userId);
    }

    /**
     * Получить записи всех пользователей (для администратора)
     */
    public static function allUsers(): Builder
    {
        return static::withoutGlobalScope(UserScope::class);
    }

    /**
     * Получить имя таблицы
     */
    protected static function getTableName(): string
    {
        return (new static)->getTable();
    }
}
