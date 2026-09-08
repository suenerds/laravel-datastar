<?php

declare(strict_types=1);

use Suenerds\LaravelDatastar\IsLocationEvent;

it('broadcasts the uri as a redirect script patch', function () {
    $event = new class
    {
        use IsLocationEvent;

        public function uri(): string
        {
            return '/dashboard';
        }
    };

    $lines = implode("\n", $event->broadcastWith());

    expect($event->broadcastAs())->toBe('datastar-patch-elements')
        ->and($lines)->toContain('selector body')
        ->toContain('mode append')
        ->toContain("window.location = '/dashboard'");
});
