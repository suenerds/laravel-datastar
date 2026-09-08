<?php

declare(strict_types=1);

namespace Suenerds\LaravelDatastar;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\Support\Responsable;
use Illuminate\Http\Response;
use Illuminate\Http\StreamedEvent;
use Illuminate\Support\Facades\Blade;

/**
 * @implements Arrayable<int, string>
 */
class ExecuteScript extends StreamedEvent implements Arrayable, Responsable
{
    public mixed $data {
        get => $this->data ??= $this->toStreamedEvent();
    }

    /**
     * Create a new class instance.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function __construct(
        public string $script,
        public bool $autoRemove = true,
        public array $attributes = [],
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
        $elements = Blade::render(<<<'HTML'
<script {{ (new \Illuminate\View\ComponentAttributeBag($attrs)) }} {!! $autoRemove !!}>
{!! $script !!}
</script>
HTML,
            [
                'script' => $this->script,
                'attrs' => $this->attributes,
                'autoRemove' => $this->autoRemove ? 'data-effect="el.remove()"' : '',
            ],
        );

        $data = [
            'selector' => 'body',
            'mode' => PatchMode::Append->value,
            'elements' => explode("\n", $elements),
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
            content: $this->script,
            status: 200,
            headers: [
                'Content-Type' => 'text/javascript',
                'datastar-script-attributes' => json_encode(['type' => 'module']),
            ],
        );
    }
}
