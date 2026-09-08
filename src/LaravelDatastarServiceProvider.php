<?php

declare(strict_types=1);

namespace Suenerds\LaravelDatastar;

use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\ServiceProvider;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class LaravelDatastarServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $sse = function (iterable $events): StreamedResponse {
            return response()->eventStream(function () use ($events) {
                foreach ($events as $event) {
                    yield $event;
                }
            }, endStreamWith: null);
        };

        $fragmentsAsCollection = function (View $view, ?array $fragments): Collection {
            if (! is_null($fragments)) {
                return (new Collection($fragments))->map(fn (string $fragment): string => $view->fragment($fragment));
            }

            $rendered = [];

            $view->render(function () use ($view, &$rendered): void {
                $rendered = $view->getFactory()->getFragments();
            });

            return new Collection($rendered);
        };

        Response::macro('sse', fn (array|Collection $events): StreamedResponse => $sse($events));

        Request::macro('isDatastar', function (): bool {
            return $this->hasHeader('Datastar-Request');
        });

        Request::macro('signals', function (): Signals {
            /** @var Request $this */
            if ($this->attributes->has('datastar.signals')) {
                return $this->attributes->get('datastar.signals');
            }

            $input = in_array($this->method(), ['GET', 'DELETE'])
                ? $this->query('datastar', '')
                : $this->getContent();

            $decoded = $input ? json_decode($input, true) : [];
            $decoded = is_array($decoded) ? $decoded : [];

            $signals = new Signals($decoded);

            $this->attributes->set('datastar.signals', $signals);

            return $signals;
        });

        View::macro('fragmentsAsCollection', function (?array $fragments = null) use ($fragmentsAsCollection): Collection {
            /** @var View $this */
            return $fragmentsAsCollection($this, $fragments);
        });

        View::macro('streamFragmentsIf', function (mixed $boolean, ?array $fragments = null) use ($sse, $fragmentsAsCollection): StreamedResponse|string {
            /** @var View $this */
            if (value($boolean)) {
                return $sse($fragmentsAsCollection($this, $fragments)
                    ->map(fn (string $fragment): PatchElements => new PatchElements(elements: $fragment)));
            }

            return $this->render();
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void {}
}
