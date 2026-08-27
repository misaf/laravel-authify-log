<?php

declare(strict_types=1);

namespace Misaf\LaravelAuthifyLog\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Config;
use Misaf\LaravelAuthifyLog\Database\Factories\AuthifyLogFactory;
use Misaf\LaravelAuthifyLog\Enums\AuthifyLogActionEnum;

/**
 * @property int $id
 * @property int|null $user_id
 * @property AuthifyLogActionEnum $action
 * @property string $ip_address
 * @property string $ip_country
 * @property string|null $user_agent
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
class AuthifyLog extends Model
{
    /** @use HasFactory<AuthifyLogFactory> */
    use HasFactory;

    /**
     * The table is read from the storage config so the model and the storage
     * driver can never disagree about where entries live.
     */
    public function getTable(): string
    {
        return Config::string('authify-log.storage.database.table', 'authify_logs');
    }

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'id'         => 'integer',
        'user_id'    => 'integer',
        'action'     => AuthifyLogActionEnum::class,
        'ip_address' => 'string',
        'ip_country' => 'string',
        'user_agent' => 'string',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'action',
        'ip_address',
        'ip_country',
        'user_agent',
    ];

    protected static function newFactory(): AuthifyLogFactory
    {
        return AuthifyLogFactory::new();
    }
}
