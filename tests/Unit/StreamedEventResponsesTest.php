<?php

declare(strict_types=1);

use Suenerds\LaravelDatastar\ExecuteScript;
use Suenerds\LaravelDatastar\NamespaceType;
use Suenerds\LaravelDatastar\PatchElements;
use Suenerds\LaravelDatastar\PatchMode;
use Suenerds\LaravelDatastar\RemoveElements;

beforeEach(function () {
    app('view')->addLocation(__DIR__.'/../Fixtures/views');
});

it('responds with patch elements as html and datastar headers', function () {
    $response = (new PatchElements(
        selector: '#list',
        mode: PatchMode::Inner,
        elements: '<div>a</div>',
    ))->toResponse(request());

    expect($response->getContent())->toBe('<div>a</div>')
        ->and($response->headers->get('Content-Type'))->toBe('text/html')
        ->and($response->headers->get('datastar-selector'))->toBe('#list')
        ->and($response->headers->get('datastar-mode'))->toBe('inner');
});

it('responds with namespace and view transition headers when set', function () {
    $response = (new PatchElements(
        selector: '#icon',
        namespace: NamespaceType::Svg,
        useViewTransition: true,
        elements: '<circle />',
    ))->toResponse(request());

    expect($response->headers->get('datastar-namespace'))->toBe('svg')
        ->and($response->headers->get('datastar-use-view-transition'))->toBe('true');
});

it('renders a view as the patch elements response body', function () {
    $response = (new PatchElements(elements: view('chunk')))->toResponse(request());

    expect($response->getContent())->toContain('<div>One</div>')
        ->toContain('<div>Two</div>');
});

it('omits datastar headers for unset patch element options', function () {
    $response = (new PatchElements(elements: '<div>a</div>'))->toResponse(request());

    expect($response->headers->has('datastar-selector'))->toBeFalse()
        ->and($response->headers->has('datastar-mode'))->toBeFalse()
        ->and($response->headers->has('datastar-namespace'))->toBeFalse()
        ->and($response->headers->has('datastar-use-view-transition'))->toBeFalse();
});

it('responds with the raw script as javascript', function () {
    $response = (new ExecuteScript('console.log(1)'))->toResponse(request());

    expect($response->getContent())->toBe('console.log(1)')
        ->and($response->headers->get('Content-Type'))->toBe('text/javascript')
        ->and($response->headers->get('datastar-script-attributes'))->toBe('{"type":"module"}');
});

it('responds with remove headers and an empty body', function () {
    $response = (new RemoveElements('#toast'))->toResponse(request());

    expect($response->getContent())->toBe('')
        ->and($response->headers->get('datastar-selector'))->toBe('#toast')
        ->and($response->headers->get('datastar-mode'))->toBe('remove');
});
