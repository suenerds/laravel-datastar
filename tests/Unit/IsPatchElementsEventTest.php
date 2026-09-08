<?php

declare(strict_types=1);

use Suenerds\LaravelDatastar\Tests\Fixtures\DashboardUpdatedEvent;
use Suenerds\LaravelDatastar\Tests\Fixtures\OrderShippedEvent;

it('broadcasts a view as patch element data lines', function () {
    app('view')->addLocation(__DIR__.'/../Fixtures/views');

    expect((new DashboardUpdatedEvent)->broadcastWith())->toBe([
        'elements <div>One</div>',
        'elements <div>Two</div>',
    ]);
});

it('broadcasts the elements as patch element data lines', function () {
    $event = new OrderShippedEvent;

    expect($event->broadcastWith())->toBe([
        'elements <div>shipped</div>',
        'elements <div>notified</div>',
    ]);
});

it('broadcasts as the datastar patch elements event', function () {
    expect((new OrderShippedEvent)->broadcastAs())->toBe('datastar-patch-elements');
});
