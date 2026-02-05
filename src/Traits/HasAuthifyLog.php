<?php

declare(strict_types=1);

namespace Misaf\LaravelAuthifyLog\Traits;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\Config;

/**
 * @template AuthifyLog of \Illuminate\Database\Eloquent\Model
 */
trait HasAuthifyLog
{
    /**
     * @return HasOne<AuthifyLog>
     */
    public function latestAuthifyLog(): HasOne
    {
        $modelClass = Config::string('authify-log.model');

        return $this->hasOne($modelClass)->latestOfMany();
    }

    /**
     * @return HasOne<AuthifyLog>
     */
    public function oldestAuthifyLog(): HasOne
    {
        $modelClass = Config::string('authify-log.model');

        return $this->hasOne($modelClass)->oldestOfMany();
    }

    /**
     * @return HasOne<AuthifyLog>
     */
    public function authifyLogs(): HasMany
    {
        $modelClass = Config::string('authify-log.model');

        return $this->hasMany($modelClass);
    }
}
