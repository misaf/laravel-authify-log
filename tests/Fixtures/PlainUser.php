<?php

declare(strict_types=1);

namespace Misaf\LaravelAuthifyLog\Tests\Fixtures;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

/**
 * An authenticatable with none of the package's own traits, used to prove
 * nothing the package does depends on them.
 */
class PlainUser extends Authenticatable
{
    use Notifiable;

    protected $table = 'users';

    protected $guarded = [];
}
