<?php

declare(strict_types=1);

use Suenerds\LaravelDatastar\IsPatchSignalsEvent;

it('broadcasts the signals as patch signals data lines', function () {
    $event = new class
    {
        use IsPatchSignalsEvent;

        public function signals(): array
        {
            return ['count' => 1];
        }
    };

    expect($event->broadcastWith())->toBe(['signals {"count":1}'])
        ->and($event->broadcastAs())->toBe('datastar-patch-signals');
});

it('includes the only-if-missing line when overridden', function () {
    $event = new class
    {
        use IsPatchSignalsEvent;

        public function signals(): array
        {
            return ['count' => 1];
        }

        public function onlyIfMissing(): ?bool
        {
            return true;
        }
    };

    expect($event->broadcastWith())->toBe(['onlyIfMissing true', 'signals {"count":1}']);
});
