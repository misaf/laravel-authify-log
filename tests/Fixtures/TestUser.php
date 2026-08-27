<?php

declare(strict_types=1);

namespace Misaf\LaravelAuthifyLog\Tests\Fixtures;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Misaf\LaravelAuthifyLog\Traits\HasAuthifyLog;

class TestUser extends Authenticatable
{
    use HasAuthifyLog;
    use Notifiable;

    protected $table = 'users';

    protected $guarded = [];
}
