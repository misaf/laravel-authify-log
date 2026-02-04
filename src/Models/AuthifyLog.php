<?php

declare(strict_types=1);

namespace Misaf\LaravelAuthifyLog\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Misaf\LaravelAuthifyLog\Database\Factories\AuthifyLogFactory;
use Misaf\LaravelAuthifyLog\Enums\AuthifyLogActionEnum;

/**
 * @property int $id
 * @property int $user_id
 * @property AuthifyLogActionEnum $action
 * @property string $ip_address
 * @property string $ip_country
 * @property string $user_agent
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
final class AuthifyLog extends Model
{
    /** @use HasFactory<AuthifyLogFactory> */
    use HasFactory;

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
}
