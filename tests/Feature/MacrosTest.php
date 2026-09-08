<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Suenerds\LaravelDatastar\PatchSignals;
use Suenerds\LaravelDatastar\RemoveElements;
use Symfony\Component\HttpFoundation\StreamedResponse;

use function Pest\Laravel\get;
use function Pest\Laravel\postJson;

beforeEach(function () {
    app('view')->addLocation(__DIR__.'/../Fixtures/views');
});

it('streams events through the sse response macro', function () {
    Route::get('/sse', fn () => response()->sse([new RemoveElements('#toast')]));

    $response = get('/sse');

    $response->assertOk();

    expect($response->streamedContent())
        ->toContain('event: datastar-patch-elements')
        ->toContain('data: selector #toast')
        ->toContain('data: mode remove');
});

it('collects all rendered fragments from a view', function () {
    $fragments = view('items')->fragmentsAsCollection();

    expect($fragments->values()->all())->toBe(['<p>Fragment One</p>', '<p>Fragment Two</p>']);
});

it('collects only the requested fragments from a view', function () {
    $fragments = view('items')->fragmentsAsCollection(['second']);

    expect($fragments->values()->all())->toBe(['<p>Fragment Two</p>']);
});

it('streams view fragments as patch element events when the condition passes', function () {
    Route::get('/fragments', fn () => view('items')->streamFragmentsIf(true));

    $response = get('/fragments');

    $response->assertOk();

    expect($response->baseResponse)->toBeInstanceOf(StreamedResponse::class)
        ->and($response->streamedContent())
        ->toContain('event: datastar-patch-elements')
        ->toContain('data: elements <p>Fragment One</p>')
        ->toContain('data: elements <p>Fragment Two</p>');
});

it('renders the full view when the stream condition fails', function () {
    Route::get('/fragments', fn () => view('items')->streamFragmentsIf(false));

    get('/fragments')
        ->assertOk()
        ->assertSee('Fragment One')
        ->assertSee('Fragment Two');
});

it('streams multiple events from a collection through the sse macro', function () {
    Route::get('/sse', fn () => response()->sse(collect([
        new RemoveElements('#toast'),
        new PatchSignals(['count' => 1]),
    ])));

    $content = get('/sse')->streamedContent();

    expect($content)
        ->toContain('event: datastar-patch-elements')
        ->toContain('data: selector #toast')
        ->toContain('event: datastar-patch-signals')
        ->toContain('data: signals {"count":1}');
});

it('streams only the named fragments when the condition is a truthy closure', function () {
    Route::get('/fragments', fn () => view('items')->streamFragmentsIf(fn (): bool => true, ['first']));

    $content = get('/fragments')->streamedContent();

    expect($content)->toContain('data: elements <p>Fragment One</p>')
        ->not->toContain('Fragment Two');
});

it('renders the full view when the stream condition is a falsy closure', function () {
    Route::get('/fragments', fn () => view('items')->streamFragmentsIf(fn (): bool => false));

    get('/fragments')
        ->assertOk()
        ->assertSee('Fragment One')
        ->assertSee('Fragment Two');
});

it('collects no fragments from a view without fragments', function () {
    expect(view('plain')->fragmentsAsCollection())->toBeEmpty();
});

it('parses signals from the request body via the signals macro', function () {
    Route::post('/signals', fn () => response()->json(request()->signals()->toArray()));

    postJson('/signals', ['name' => 'Thore', 'count' => 2])
        ->assertOk()
        ->assertExactJson(['name' => 'Thore', 'count' => 2]);
});

it('parses signals from the datastar query parameter via the signals macro', function () {
    Route::get('/signals', fn () => response()->json(request()->signals()->toArray()));

    get('/signals?datastar='.urlencode(json_encode(['name' => 'Thore'])))
        ->assertOk()
        ->assertExactJson(['name' => 'Thore']);
});

it('caches the signals instance on the request', function () {
    Route::post('/signals', function () {
        return response()->json([
            'same' => request()->signals() === request()->signals(),
        ]);
    });

    postJson('/signals', ['name' => 'Thore'])->assertExactJson(['same' => true]);
});

it('returns empty signals for a request without a datastar payload', function () {
    Route::get('/signals', fn () => response()->json(request()->signals()->toArray()));

    get('/signals')->assertOk()->assertExactJson([]);
});

it('detects datastar requests via the request macro', function () {
    Route::get('/detect', fn () => response()->json(['datastar' => request()->isDatastar()]));

    get('/detect', ['Datastar-Request' => 'true'])->assertJson(['datastar' => true]);
    get('/detect')->assertJson(['datastar' => false]);
});
