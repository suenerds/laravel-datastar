<?php

declare(strict_types=1);

namespace Suenerds\LaravelDatastar;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\Support\Responsable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\StreamedEvent;

/**
 * @implements Arrayable<int, string>
 */
class PatchSignals extends StreamedEvent implements Arrayable, Responsable
{
    public mixed $data {
        get => $this->data ??= $this->toStreamedEvent();
    }

    /**
     * Create a new class instance.
     *
     * @param  array<string, mixed>  $signals
     */
    public function __construct(
        public array $signals,
        public ?bool $onlyIfMissing = null,
    ) {
        parent::__construct(event: 'datastar-patch-signals', data: null);
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
            'onlyIfMissing' => $this->onlyIfMissing(),
            'signals' => json_encode($this->signals, JSON_THROW_ON_ERROR),
        ];

        return collect($data)
            ->filter()
            ->map(fn (string $value, string $key): string => "$key $value")
            ->values()
            ->all();
    }

    public function toResponse(mixed $request): JsonResponse
    {
        return response()
            ->json(
                data: $this->signals,
                headers: array_filter([
                    'datastar-only-if-missing' => $this->onlyIfMissing(),
                ]),
            );
    }

    public function onlyIfMissing(): ?string
    {
        return $this->onlyIfMissing ? 'true' : null;
    }
}
