<?php

declare(strict_types=1);

namespace Suenerds\LaravelDatastar;

trait IsExecuteScriptEvent
{
    abstract public function script(): string;

    public function autoRemove(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function attributes(): array
    {
        return [];
    }

    /**
     * @return array<int, string>
     */
    public function broadcastWith(): array
    {
        return new ExecuteScript(
            script: $this->script(),
            autoRemove: $this->autoRemove(),
            attributes: $this->attributes(),
        )->toArray();
    }

    public function broadcastAs(): string
    {
        return 'datastar-patch-elements';
    }
}
