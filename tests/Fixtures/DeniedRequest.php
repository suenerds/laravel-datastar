<?php

declare(strict_types=1);

namespace Suenerds\LaravelDatastar\Tests\Fixtures;

use Suenerds\LaravelDatastar\DatastarRequest;

class DeniedRequest extends DatastarRequest
{
    public function authorize(): bool
    {
        return false;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [];
    }
}
