<?php

declare(strict_types=1);

use Suenerds\LaravelDatastar\IsRemoveElementsEvent;

it('broadcasts the selector as a remove-mode patch', function () {
    $event = new class
    {
        use IsRemoveElementsEvent;

        public function selector(): string
        {
            return '#toast';
        }
    };

    expect($event->broadcastWith())->toBe(['selector #toast', 'mode remove'])
        ->and($event->broadcastAs())->toBe('datastar-patch-elements');
});
