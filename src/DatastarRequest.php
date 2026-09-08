<?php

declare(strict_types=1);

namespace Suenerds\LaravelDatastar;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\View;

abstract class DatastarRequest extends FormRequest
{
    /** @var array<string, mixed>|null */
    protected ?array $rawSignalData = null;

    protected ?Signals $validatedSignals = null;

    protected function prepareForValidation(): void
    {
        $this->merge($this->rawSignalData());
    }

    /**
     * Decode the datastar payload exactly once.
     *
     * @return array<string, mixed>
     */
    protected function rawSignalData(): array
    {
        if ($this->rawSignalData !== null) {
            return $this->rawSignalData;
        }

        $input = in_array($this->method(), ['GET', 'DELETE'])
            ? $this->query('datastar', '')
            : $this->getContent();

        $decoded = $input ? json_decode($input, true) : [];

        return $this->rawSignalData = $this->convertEmptyStringsToNull(
            is_array($decoded) ? $decoded : [],
        );
    }

    /**
     * Raw, unvalidated signals (SDK parity).
     */
    protected function rawSignals(): Signals
    {
        return new Signals($this->rawSignalData());
    }

    /**
     * Validated signals after rules pass; raw before validation runs.
     */
    public function signals(): Signals
    {
        return $this->validatedSignals ?? $this->rawSignals();
    }

    protected function passedValidation(): void
    {
        $this->validatedSignals = new Signals(
            array_intersect_key(
                $this->validated(),
                $this->rawSignalData(),
            ),
        );

        View::share('signals', $this->validatedSignals);
    }

    /**
     * Mirror Laravel's ConvertEmptyStringsToNull middleware for signal values.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function convertEmptyStringsToNull(array $data): array
    {
        array_walk_recursive($data, function (mixed &$value): void {
            if ($value === '') {
                $value = null;
            }
        });

        return $data;
    }
}
