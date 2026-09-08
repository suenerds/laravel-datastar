<?php

declare(strict_types=1);

namespace Suenerds\LaravelDatastar;

trait IsRemoveElementsEvent
{
    abstract public function selector(): string;

    /**
     * @return array<int, string>
     */
    public function broadcastWith(): array
    {
        return new RemoveElements(
            selector: $this->selector(),
        )->toArray();
    }

    public function broadcastAs(): string
    {
        return 'datastar-patch-elements';
    }
}
