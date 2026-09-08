<?php

declare(strict_types=1);

namespace Suenerds\LaravelDatastar;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\Support\Responsable;
use Illuminate\Http\Response;
use Illuminate\Http\StreamedEvent;

/**
 * @implements Arrayable<int, string>
 */
class RemoveElements extends StreamedEvent implements Arrayable, Responsable
{
    public mixed $data {
        get => $this->data ??= $this->toStreamedEvent();
    }

    /**
     * Create a new class instance.
     */
    public function __construct(
        public string $selector,
    ) {
        parent::__construct(event: 'datastar-patch-elements', data: null);
    }

    public function toStreamedEvent(): string
    {
        return implode("\ndata: ", $this->toArray());
    }

    /**
     * @return array<int, string>
     */
    public function toArray(): array
    {
        $data = [
            'selector' => $this->selector,
            'mode' => PatchMode::Remove->value,
        ];

        return collect($data)
            ->filter()
            ->map(fn (string $value, string $key): string => "$key $value")
            ->values()
            ->all();
    }

    public function toResponse(mixed $request): Response
    {
        return response(
            status: 200,
            headers: array_filter([
                'Content-Type' => 'text/html',
                'datastar-selector' => $this->selector,
                'datastar-mode' => PatchMode::Remove->value,
            ]),
        );
    }
}
