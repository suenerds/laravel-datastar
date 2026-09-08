<?php

declare(strict_types=1);

use Illuminate\Foundation\Http\Middleware\HandlePrecognitiveRequests;
use Illuminate\Support\Facades\Route;
use Suenerds\LaravelDatastar\Tests\Fixtures\CreateUserRequest;

use function Pest\Laravel\post;
use function Pest\Laravel\postJson;

beforeEach(function () {
    Route::post('/precognitive', function (CreateUserRequest $request) {
        return response()->json(['controller' => 'executed']);
    })->middleware([HandlePrecognitiveRequests::class]);
});

it('validates signals precognitively without running the controller', function () {
    postJson('/precognitive', ['name' => 'Thore'], ['Precognition' => 'true'])
        ->assertNoContent()
        ->assertHeader('Precognition-Success', 'true');
});

it('returns validation errors for precognitive requests', function () {
    postJson('/precognitive', ['email' => 'not-an-email'], ['Precognition' => 'true'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['name', 'email']);
});

it('validates only the requested signals when limited', function () {
    postJson('/precognitive', ['email' => 'thore@example.com'], [
        'Precognition' => 'true',
        'Precognition-Validate-Only' => 'email',
    ])
        ->assertNoContent()
        ->assertHeader('Precognition-Success', 'true');
});

it('validates form-encoded precognitive submissions field by field', function () {
    post('/precognitive', ['email' => 'not-an-email'], [
        'Accept' => 'application/json',
        'Precognition' => 'true',
        'Precognition-Validate-Only' => 'email',
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['email'])
        ->assertJsonMissingValidationErrors(['name']);

    post('/precognitive', ['email' => 'thore@example.com'], [
        'Accept' => 'application/json',
        'Precognition' => 'true',
        'Precognition-Validate-Only' => 'email',
    ])
        ->assertNoContent()
        ->assertHeader('Precognition-Success', 'true');
});

it('runs the controller for real submissions', function () {
    postJson('/precognitive', ['name' => 'Thore'])
        ->assertOk()
        ->assertExactJson(['controller' => 'executed']);
});
