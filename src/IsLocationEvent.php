<?php

declare(strict_types=1);

namespace Suenerds\LaravelDatastar;

trait IsLocationEvent
{
    abstract public function uri(): string;

    /**
     * @return array<int, string>
     */
    public function broadcastWith(): array
    {
        return new Location(
            uri: $this->uri(),
        )->toArray();
    }

    public function broadcastAs(): string
    {
        return 'datastar-patch-elements';
    }
}
