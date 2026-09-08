<?php

declare(strict_types=1);

use Suenerds\LaravelDatastar\ExecuteScript;
use Suenerds\LaravelDatastar\Location;
use Suenerds\LaravelDatastar\NamespaceType;
use Suenerds\LaravelDatastar\PatchElements;
use Suenerds\LaravelDatastar\PatchMode;
use Suenerds\LaravelDatastar\RemoveElements;

beforeEach(function () {
    app('view')->addLocation(__DIR__.'/../Fixtures/views');
});

it('renders patch elements as sse data lines', function () {
    $event = new PatchElements(
        selector: '#list',
        mode: PatchMode::Inner,
        elements: "<div>a</div>\n<div>b</div>",
    );

    expect($event->event)->toBe('datastar-patch-elements')
        ->and($event->data)->toBe(
            "selector #list\ndata: mode inner\ndata: elements <div>a</div>\ndata: elements <div>b</div>",
        );
});

it('renders a view as patch element lines', function () {
    $event = new PatchElements(selector: '#list', elements: view('chunk'));

    expect($event->data)->toBe(
        "selector #list\ndata: elements <div>One</div>\ndata: elements <div>Two</div>",
    );
});

it('includes namespace and view transition data lines when set', function () {
    $event = new PatchElements(
        selector: '#icon',
        mode: PatchMode::Outer,
        namespace: NamespaceType::Svg,
        useViewTransition: true,
        elements: '<circle />',
    );

    expect($event->data)->toBe(
        "selector #icon\ndata: mode outer\ndata: namespace svg\ndata: useViewTransition true\ndata: elements <circle />",
    );
});

it('renders only the given options when elements are absent', function () {
    $event = new PatchElements(selector: '#list', mode: PatchMode::Inner);

    expect($event->data)->toBe("selector #list\ndata: mode inner");
});

it('renders custom attributes on the script element', function () {
    $event = new ExecuteScript('console.log(1)', attributes: ['type' => 'module']);

    expect($event->data)->toContain('type="module"');
});

it('renders remove elements as a remove-mode patch', function () {
    $event = new RemoveElements('#toast');

    expect($event->event)->toBe('datastar-patch-elements')
        ->and($event->data)->toBe("selector #toast\ndata: mode remove");
});

it('renders execute script as an appended script element', function () {
    $event = new ExecuteScript('console.log(1)');

    expect($event->event)->toBe('datastar-patch-elements')
        ->and($event->data)->toContain('selector body')
        ->toContain('mode append')
        ->toContain('console.log(1)')
        ->toContain('data-effect="el.remove()"');
});

it('keeps the script element when auto remove is disabled', function () {
    $event = new ExecuteScript('console.log(1)', autoRemove: false);

    expect($event->data)->not->toContain('data-effect="el.remove()"');
});

it('renders a location event as a redirect script', function () {
    $event = new Location('/dashboard');

    expect($event->data)->toContain("window.location = '/dashboard'");
});
