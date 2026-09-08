<?php

declare(strict_types=1);

namespace Suenerds\LaravelDatastar;

use Illuminate\View\View;

trait IsPatchElementsEvent
{
    abstract public function elements(): string|View;

    /**
     * @return array<int, string>
     */
    public function broadcastWith(): array
    {
        return new PatchElements(
            elements: $this->elements(),
        )->toArray();
    }

    public function broadcastAs(): string
    {
        return 'datastar-patch-elements';
    }
}
