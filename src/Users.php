<?php

declare(strict_types=1);

namespace Misaf\LaravelAuthifyLog;

use Illuminate\Database\Eloquent\Model;
use Misaf\LaravelAuthifyLog\Contracts\ResolvesUsers;

/**
 * Resolves the name used to greet a notifiable, guessing at the usual
 * attributes unless the application supplies its own resolver.
 */
class Users implements ResolvesUsers
{
    /**
     * @var (callable(object): string)|null
     */
    protected $resolver;

    /**
     * Resolve names using the given callback.
     *
     * @param callable(object): string $resolver
     */
    public function resolveUsing(callable $resolver): self
    {
        $this->resolver = $resolver;

        return $this;
    }

    public function name(object $user): string
    {
        if (null !== $this->resolver) {
            return ($this->resolver)($user);
        }

        if ( ! $user instanceof Model) {
            return '';
        }

        foreach (['name', 'username', 'email'] as $attribute) {
            $value = $user->getAttribute($attribute);

            if (is_string($value) && '' !== $value) {
                return $value;
            }
        }

        return '';
    }
}
