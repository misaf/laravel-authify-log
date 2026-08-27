<?php

declare(strict_types=1);

namespace Misaf\LaravelAuthifyLog\Contracts;

interface Recorder
{
    /**
     * The events this recorder listens for.
     *
     * @return list<class-string>
     */
    public function listen(): array;

    /**
     * Record the given event.
     */
    public function record(object $event): void;
}
