<?php

declare(strict_types=1);

namespace Misaf\LaravelAuthifyLog\Traits;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\Config;

/**
 * @phpstan-require-extends Model
 */
trait HasAuthifyLog
{
    /**
     * @return HasOne<Model, $this>
     */
    public function latestAuthifyLog(): HasOne
    {
        return $this->hasOne(self::authifyLogModel(), self::authifyLogForeignKey())->latestOfMany();
    }

    /**
     * @return HasOne<Model, $this>
     */
    public function oldestAuthifyLog(): HasOne
    {
        return $this->hasOne(self::authifyLogModel(), self::authifyLogForeignKey())->oldestOfMany();
    }

    /**
     * @return HasMany<Model, $this>
     */
    public function authifyLogs(): HasMany
    {
        return $this->hasMany(self::authifyLogModel(), self::authifyLogForeignKey());
    }

    /**
     * @return class-string<Model>
     */
    private static function authifyLogModel(): string
    {
        /** @var class-string<Model> $modelClass */
        $modelClass = Config::string('authify-log.model');

        return $modelClass;
    }

    /**
     * The column is always `user_id`, regardless of what the parent model is
     * called, so it must be named explicitly rather than inferred.
     */
    private static function authifyLogForeignKey(): string
    {
        return Config::string('authify-log.foreign_key');
    }
}
