<?php

declare(strict_types=1);

namespace Suenerds\LaravelDatastar;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\Support\Responsable;
use Illuminate\Http\Response;
use Illuminate\Http\StreamedEvent;
use Illuminate\View\View;

/**
 * @implements Arrayable<int, string>
 */
class PatchElements extends StreamedEvent implements Arrayable, Responsable
{
    public mixed $data {
        get => $this->data ??= $this->toStreamedEvent();
    }

    /**
     * Create a new class instance.
     */
    public function __construct(
        public ?string $selector = null,
        public ?PatchMode $mode = null,
        public ?NamespaceType $namespace = null,
        public bool $useViewTransition = false,
        public string|View|null $elements = null,
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
        $elements = match (true) {
            $this->elements instanceof View => explode("\n", $this->elements->render()),
            is_string($this->elements) => preg_split('/\r\n|\r|\n/', $this->elements) ?: [],
            default => null,
        };

        $data = [
            'selector' => $this->selector,
            'mode' => $this->mode?->value,
            'namespace' => $this->namespace?->value,
            'useViewTransition' => $this->useViewTransition ? 'true' : null,
            'elements' => $elements,
        ];

        return collect($data)
            ->filter()
            ->flatMap(function (array|string $value, string $key): array {
                return is_array($value)
                    ? collect($value)->filter()->map(fn (string $v): string => "$key $v")->all()
                    : ["$key $value"];
            })
            ->values()
            ->all();
    }

    public function toResponse(mixed $request): Response
    {
        return response(
            content: $this->elements,
            status: 200,
            headers: array_filter([
                'Content-Type' => 'text/html',
                'datastar-selector' => $this->selector,
                'datastar-mode' => $this->mode?->value,
                'datastar-namespace' => $this->namespace?->value,
                'datastar-use-view-transition' => $this->useViewTransition ? 'true' : null,
            ]),
        );
    }
}
