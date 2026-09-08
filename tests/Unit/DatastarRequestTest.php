<?php

declare(strict_types=1);

use Suenerds\LaravelDatastar\Signals;
use Suenerds\LaravelDatastar\Tests\Fixtures\CreateUserRequest;

it('exposes raw signals before validation has run', function () {
    $request = CreateUserRequest::create('/datastar', 'POST',
        server: ['CONTENT_TYPE' => 'application/json'],
        content: json_encode(['name' => 'Thore', 'unruled' => 'kept']),
    );

    expect($request->signals())
        ->toBeInstanceOf(Signals::class)
        ->toArray()->toBe(['name' => 'Thore', 'unruled' => 'kept']);
});

it('reads raw signals from the datastar query parameter for GET requests', function () {
    $request = CreateUserRequest::create('/datastar?datastar='.urlencode(json_encode(['name' => 'Thore'])));

    expect($request->signals()->toArray())->toBe(['name' => 'Thore']);
});

it('ignores the request body for GET requests', function () {
    $request = CreateUserRequest::create('/datastar', 'GET',
        content: json_encode(['name' => 'from-body']),
    );

    expect($request->signals()->toArray())->toBe([]);
});

it('converts empty strings to null recursively in raw signals', function () {
    $request = CreateUserRequest::create('/datastar', 'POST',
        server: ['CONTENT_TYPE' => 'application/json'],
        content: json_encode(['email' => '', 'profile' => ['bio' => '']]),
    );

    expect($request->signals()->toArray())->toBe([
        'email' => null,
        'profile' => ['bio' => null],
    ]);
});

it('returns empty signals for a malformed json body', function () {
    $request = CreateUserRequest::create('/datastar', 'POST',
        server: ['CONTENT_TYPE' => 'application/json'],
        content: 'not json',
    );

    expect($request->signals()->toArray())->toBe([]);
});

it('returns empty signals when the json body is not an object', function () {
    $request = CreateUserRequest::create('/datastar', 'POST',
        server: ['CONTENT_TYPE' => 'application/json'],
        content: '"scalar"',
    );

    expect($request->signals()->toArray())->toBe([]);
});

it('returns empty signals when no payload is present', function () {
    $request = CreateUserRequest::create('/datastar', 'POST');

    expect($request->signals()->toArray())->toBe([]);
});
