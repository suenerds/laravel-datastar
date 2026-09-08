<?php

declare(strict_types=1);

namespace Suenerds\LaravelDatastar;

trait IsPatchSignalsEvent
{
    /**
     * @return array<string, mixed>
     */
    abstract public function signals(): array;

    public function onlyIfMissing(): ?bool
    {
        return null;
    }

    /**
     * @return array<int, string>
     */
    public function broadcastWith(): array
    {
        return new PatchSignals(
            signals: $this->signals(),
            onlyIfMissing: $this->onlyIfMissing(),
        )->toArray();
    }

    public function broadcastAs(): string
    {
        return 'datastar-patch-signals';
    }
}
