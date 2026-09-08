<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\View;
use Suenerds\LaravelDatastar\Signals;
use Suenerds\LaravelDatastar\Tests\Fixtures\CreateUserRequest;
use Suenerds\LaravelDatastar\Tests\Fixtures\DeniedRequest;

use function Pest\Laravel\call;
use function Pest\Laravel\deleteJson;
use function Pest\Laravel\getJson;
use function Pest\Laravel\patchJson;
use function Pest\Laravel\postJson;
use function Pest\Laravel\putJson;

beforeEach(function () {
    Route::match(['GET', 'POST', 'PUT', 'PATCH', 'DELETE'], '/datastar', function (CreateUserRequest $request) {
        return response()->json($request->signals()->toArray());
    });
});

it('validates signals from the request body on POST', function () {
    postJson('/datastar', ['name' => 'Thore', 'email' => 'thore@example.com'])
        ->assertOk()
        ->assertExactJson(['name' => 'Thore', 'email' => 'thore@example.com']);
});

it('validates signals from the datastar query parameter on GET', function () {
    getJson('/datastar?datastar='.urlencode(json_encode(['name' => 'Thore'])))
        ->assertOk()
        ->assertExactJson(['name' => 'Thore']);
});

it('validates signals from the datastar query parameter on DELETE', function () {
    deleteJson('/datastar?datastar='.urlencode(json_encode(['name' => 'Thore'])))
        ->assertOk()
        ->assertExactJson(['name' => 'Thore']);
});

it('validates signals from the request body on PUT', function () {
    putJson('/datastar', ['name' => 'Thore'])
        ->assertOk()
        ->assertExactJson(['name' => 'Thore']);
});

it('validates signals from the request body on PATCH', function () {
    patchJson('/datastar', ['name' => 'Thore'])
        ->assertOk()
        ->assertExactJson(['name' => 'Thore']);
});

it('rejects unauthorized datastar requests', function () {
    Route::post('/denied', fn (DeniedRequest $request) => response()->json());

    postJson('/denied', ['name' => 'Thore'])->assertForbidden();
});

it('converts empty string signals to null before validation', function () {
    postJson('/datastar', [
        'name' => 'Thore',
        'email' => '',
        'profile' => ['bio' => ''],
    ])
        ->assertOk()
        ->assertExactJson([
            'name' => 'Thore',
            'email' => null,
            'profile' => ['bio' => null],
        ]);
});

it('excludes validated input that was not part of the signal payload', function () {
    postJson('/datastar?source=web', ['name' => 'Thore'])
        ->assertOk()
        ->assertExactJson(['name' => 'Thore']);
});

it('excludes signal keys without validation rules from validated signals', function () {
    postJson('/datastar', ['name' => 'Thore', 'unruled' => 'ignored'])
        ->assertOk()
        ->assertExactJson(['name' => 'Thore']);
});

it('fails validation when required signals are missing', function () {
    postJson('/datastar', [])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['name']);
});

it('fails validation when the body is not valid json', function () {
    call('POST', '/datastar', server: [
        'CONTENT_TYPE' => 'application/json',
        'HTTP_ACCEPT' => 'application/json',
    ], content: 'not json')
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['name']);
});

it('fails validation when the datastar query parameter is missing on GET', function () {
    getJson('/datastar')
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['name']);
});

it('shares the validated signals with all views', function () {
    Route::post('/datastar-shared', function (CreateUserRequest $request) {
        $shared = View::shared('signals');

        return response()->json([
            'shared' => $shared instanceof Signals ? $shared->toArray() : null,
            'same_instance' => $shared === $request->signals(),
        ]);
    });

    postJson('/datastar-shared', ['name' => 'Thore'])
        ->assertOk()
        ->assertExactJson([
            'shared' => ['name' => 'Thore'],
            'same_instance' => true,
        ]);
});
