<?php

declare(strict_types=1);

use Suenerds\LaravelDatastar\IsExecuteScriptEvent;

it('broadcasts the script as an appended script element', function () {
    $event = new class
    {
        use IsExecuteScriptEvent;

        public function script(): string
        {
            return 'console.log(1)';
        }
    };

    $lines = implode("\n", $event->broadcastWith());

    expect($event->broadcastAs())->toBe('datastar-patch-elements')
        ->and($lines)->toContain('selector body')
        ->toContain('mode append')
        ->toContain('console.log(1)')
        ->toContain('data-effect="el.remove()"');
});

it('honours auto remove and attribute overrides', function () {
    $event = new class
    {
        use IsExecuteScriptEvent;

        public function script(): string
        {
            return 'console.log(1)';
        }

        public function autoRemove(): bool
        {
            return false;
        }

        public function attributes(): array
        {
            return ['type' => 'module'];
        }
    };

    $lines = implode("\n", $event->broadcastWith());

    expect($lines)->toContain('type="module"')
        ->not->toContain('data-effect="el.remove()"');
});
