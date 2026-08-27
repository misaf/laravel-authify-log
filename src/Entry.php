<?php

declare(strict_types=1);

namespace Misaf\LaravelAuthifyLog;

use Illuminate\Support\Carbon;

/**
 * A single authentication event on its way to storage.
 *
 * Instances are serialized onto the ingest stream, so every property is a
 * scalar: nothing here may pull an object graph along with it.
 */
class Entry
{
    public function __construct(
        public int $timestamp,
        public int $action,
        public ?int $userId,
        public string $ipAddress,
        public string $ipCountry,
        public string $userAgent,
    ) {}

    /**
     * Fetch the entry attributes for persisting.
     *
     * @return array{user_id: int|null, action: int, ip_address: string, ip_country: string, user_agent: string, created_at: string, updated_at: string}
     */
    public function attributes(): array
    {
        $recordedAt = Carbon::createFromTimestamp($this->timestamp)->toDateTimeString();

        return [
            'user_id'    => $this->userId,
            'action'     => $this->action,
            'ip_address' => $this->ipAddress,
            'ip_country' => $this->ipCountry,
            'user_agent' => $this->userAgent,
            'created_at' => $recordedAt,
            'updated_at' => $recordedAt,
        ];
    }
}
