<?php

declare(strict_types=1);

use Suenerds\LaravelDatastar\PatchSignals;

it('renders patch signals as a key-prefixed sse data line', function () {
    $event = new PatchSignals(['count' => 1]);

    expect($event->event)->toBe('datastar-patch-signals')
        ->and($event->data)->toBe('signals {"count":1}');
});

it('includes the only-if-missing flag when enabled', function () {
    $event = new PatchSignals(['count' => 1], onlyIfMissing: true);

    expect($event->data)->toBe("onlyIfMissing true\ndata: signals {\"count\":1}");
});

it('omits the only-if-missing flag when disabled', function () {
    $event = new PatchSignals(['count' => 1], onlyIfMissing: false);

    expect($event->data)->toBe('signals {"count":1}');
});

it('throws when the signals cannot be json encoded', function () {
    $event = new PatchSignals(['value' => INF]);

    expect(fn () => $event->toArray())->toThrow(JsonException::class);
});

it('renders an empty signal set as an empty json object line', function () {
    expect((new PatchSignals([]))->data)->toBe('signals []');
});

it('responds with the signals as json and an only-if-missing header', function () {
    $event = new PatchSignals(['count' => 1], onlyIfMissing: true);

    $response = $event->toResponse(request());

    expect($response->getData(true))->toBe(['count' => 1])
        ->and($response->headers->get('datastar-only-if-missing'))->toBe('true');
});

it('responds without the only-if-missing header when not enabled', function () {
    $event = new PatchSignals(['count' => 1]);

    expect($event->toResponse(request())->headers->has('datastar-only-if-missing'))->toBeFalse();
});
