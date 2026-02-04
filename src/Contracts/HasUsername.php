<?php

declare(strict_types=1);

namespace Misaf\LaravelAuthifyLog\Contracts;

interface HasUsername
{
    public function getAuthifyLogUsername(): string;
}
