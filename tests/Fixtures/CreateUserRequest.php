<?php

declare(strict_types=1);

namespace Suenerds\LaravelDatastar\Tests\Fixtures;

use Suenerds\LaravelDatastar\DatastarRequest;

class CreateUserRequest extends DatastarRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string'],
            'email' => ['nullable', 'email'],
            'profile' => ['sometimes', 'array'],
            'profile.bio' => ['nullable', 'string'],
            'source' => ['sometimes', 'string'],
        ];
    }
}
