<?php

declare(strict_types=1);

namespace Misaf\LaravelAuthifyLog\Database\Factories;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Factories\Factory;
use Misaf\LaravelAuthifyLog\Enums\AuthifyLogActionEnum;
use Misaf\LaravelAuthifyLog\Models\AuthifyLog;

/**
 * @extends Factory<AuthifyLog>
 */
final class AuthifyLogFactory extends Factory
{
    /**
     * @var class-string<AuthifyLog>
     */
    protected $model = AuthifyLog::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id'    => null,
            'action'     => $this->faker->randomElement(AuthifyLogActionEnum::cases()),
            'ip_address' => $this->faker->ipv4(),
            'ip_country' => mb_strtoupper($this->faker->countryCode()),
            'user_agent' => $this->faker->userAgent(),
        ];
    }

    public function forUser(Authenticatable $user): static
    {
        return $this->state(fn(): array => [
            'user_id' => $user->getAuthIdentifier(),
        ]);
    }

    public function action(AuthifyLogActionEnum $action): static
    {
        return $this->state(fn(): array => [
            'action' => $action,
        ]);
    }
}
