<div align="center">
    <h1>Laravel Datastar</h1>
</div>

<p align="center">
    <a href="https://packagist.org/packages/suenerds/laravel-datastar"><img src="https://img.shields.io/packagist/v/suenerds/laravel-datastar.svg?style=flat-square" alt="Packagist"></a>
    <a href="https://packagist.org/packages/suenerds/laravel-datastar"><img src="https://img.shields.io/packagist/php-v/suenerds/laravel-datastar.svg?style=flat-square" alt="PHP from Packagist"></a>
    <a href="https://packagist.org/packages/suenerds/laravel-datastar"><img src="https://badge.laravel.cloud/badge/suenerds/laravel-datastar?style=flat" alt="Laravel versions"></a>
    <a href="https://github.com/suenerds/laravel-datastar/actions"><img alt="GitHub Workflow Status (main)" src="https://img.shields.io/github/actions/workflow/status/suenerds/laravel-datastar/tests.yml?branch=main&label=Tests&style=flat-square"></a>
    <a href="https://packagist.org/packages/suenerds/laravel-datastar"><img src="https://img.shields.io/packagist/dt/suenerds/laravel-datastar.svg?style=flat-square" alt="Total Downloads"></a>
</p>

Integrate [Datastar](https://data-star.dev) into Laravel — the Laravel way.

The package gives you:

- **Signal validation** through a familiar `FormRequest` subclass
- **Request macros** to read signals and detect Datastar requests
- **Server-Sent Event streaming** of Datastar events with `response()->sse()`
- **Event classes** for every Datastar event type, usable as SSE events *or* plain HTTP responses
- **Blade fragment streaming** so a single view can serve both full pages and partial updates
- **Broadcasting traits** to push Datastar patches over Laravel Echo channels

## Requirements

- PHP 8.4+
- Laravel 12 or 13

## Installation

Install the package via Composer:

```bash
composer require suenerds/laravel-datastar
```

Add Datastar itself to your frontend by following the [Datastar installation guide](https://data-star.dev/guide/getting_started). The package registers everything else automatically — there is no configuration to publish.

## At a glance

```blade
<div data-signals="{count: 0}">
    <button data-on-click="@post('/increment')">
        Count: <span data-text="$count"></span>
    </button>
</div>
```

```php
use Suenerds\LaravelDatastar\PatchSignals;

Route::post('/increment', function () {
    return new PatchSignals(['count' => request()->signals()->count + 1]);
});
```

## Reading signals

Datastar sends its signals as JSON — in the request body, or in the `datastar` query parameter for `GET` and `DELETE` requests. The `signals()` request macro handles both and returns a `Signals` object (an [Illuminate `Fluent`](https://laravel.com/api/12.x/Illuminate/Support/Fluent.html)):

```php
$signals = request()->signals();

$signals->count;          // property access
$signals['count'];        // array access
$signals->get('missing', 'default');
$signals->toArray();
```

The parsed instance is cached on the request, so repeated calls are free.

To detect whether a request came from Datastar (for example, to decide between a fragment stream and a full page), use the `isDatastar()` macro — it checks the `Datastar-Request` header:

```php
if (request()->isDatastar()) {
    // respond with a patch instead of a full page
}
```

## Validating signals

For anything beyond reading raw values, extend `DatastarRequest`. It is a regular `FormRequest`, so `rules()`, `authorize()`, messages, and everything else you know just work — but the input under validation is the Datastar signal payload:

```php
use Suenerds\LaravelDatastar\DatastarRequest;

class UpdateProfileRequest extends DatastarRequest
{
    public function rules(): array
    {
        return [
            'name' => ['required', 'string'],
            'email' => ['nullable', 'email'],
        ];
    }
}
```

```php
Route::put('/profile', function (UpdateProfileRequest $request) {
    $signals = $request->signals(); // validated signals

    // ...
});
```

`DatastarRequest` behaves like Laravel would:

- Empty string signals are converted to `null` before validation (recursively), mirroring the `ConvertEmptyStringsToNull` middleware.
- Failed validation responds with a `422` and the usual error payload; failed authorization with a `403`.
- `signals()` returns the **validated** signals once validation has passed — only signal keys that have rules, never query-string or other non-signal input. Before validation runs (or in `prepareForValidation`), it returns the raw signals.
- The validated signals are shared with all views as `$signals`, so any Blade view rendered during the request can read them directly.

## Responding with Datastar events

Every Datastar event type has a dedicated class:

| Class | Purpose |
| --- | --- |
| `PatchElements` | Patch HTML elements into the DOM |
| `PatchSignals` | Update client-side signals |
| `RemoveElements` | Remove elements matching a selector |
| `ExecuteScript` | Run a script in the browser |
| `Location` | Redirect the browser via script |

Each class can be **streamed over SSE** or **returned directly from a route** as an HTTP response — Datastar understands both.

### Patching elements

```php
use Suenerds\LaravelDatastar\NamespaceType;
use Suenerds\LaravelDatastar\PatchElements;
use Suenerds\LaravelDatastar\PatchMode;

new PatchElements(elements: view('cart.items'));                    // morph by id (Datastar default)
new PatchElements(selector: '#cart', mode: PatchMode::Inner, elements: '<li>…</li>');
new PatchElements(
    selector: '#chart',
    mode: PatchMode::Append,
    namespace: NamespaceType::Svg,
    useViewTransition: true,
    elements: '<circle r="4" />',
);
```

`elements` accepts an HTML string or a view instance. `PatchMode` offers `Outer`, `Inner`, `Replace`, `Prepend`, `Append`, `Before`, `After`, and `Remove`; `NamespaceType` offers `Svg` and `Mathml`. Options you leave unset are omitted, letting Datastar apply its defaults.

### Updating signals

```php
use Suenerds\LaravelDatastar\PatchSignals;

new PatchSignals(['count' => 5, 'user' => ['name' => 'Thore']]);
new PatchSignals(['theme' => 'dark'], onlyIfMissing: true); // only set signals the client doesn't have yet
```

If the signals cannot be JSON-encoded, a `JsonException` is thrown rather than silently sending a broken event.

### Removing elements & running scripts

```php
use Suenerds\LaravelDatastar\ExecuteScript;
use Suenerds\LaravelDatastar\Location;
use Suenerds\LaravelDatastar\RemoveElements;

new RemoveElements('#toast');

new ExecuteScript("console.log('hi')");                       // script removes itself after running
new ExecuteScript('initMap()', autoRemove: false, attributes: ['type' => 'module']);

new Location('/dashboard');                                   // browser-side redirect
```

### Streaming multiple events

Use the `sse` response macro to stream any number of events in a single response:

```php
Route::post('/cart', function (AddToCartRequest $request) {
    // ...

    return response()->sse([
        new PatchElements(selector: '#cart', mode: PatchMode::Inner, elements: view('cart.items')),
        new PatchSignals(['cartCount' => $count]),
        new RemoveElements('#empty-cart-hint'),
    ]);
});
```

It accepts an array or a `Collection`, so you can build the event list fluently.

### Returning a single event

Because every event class implements `Responsable`, you can return one straight from a route or controller — the package sends a regular HTTP response with the equivalent `datastar-*` headers instead of a stream:

```php
Route::delete('/toast', fn () => new RemoveElements('#toast'));
Route::put('/profile', fn (UpdateProfileRequest $request) => new PatchSignals(['saved' => true]));
```

## Blade fragments

Datastar pairs naturally with [Blade fragments](https://laravel.com/docs/blade#rendering-blade-fragments): one view serves the full page *and* the partial updates.

```blade
<div>
    @fragment('list')
        <ul id="todos">…</ul>
    @endfragment

    @fragment('counter')
        <span id="counter">{{ $count }}</span>
    @endfragment
</div>
```

`streamFragmentsIf` renders the full view normally, or streams the fragments as `PatchElements` events when the condition passes:

```php
Route::get('/todos', function () {
    return view('todos.index')->streamFragmentsIf(request()->isDatastar());
});
```

The condition may be a boolean or a closure, and you can limit the stream to specific fragments:

```php
view('todos.index')->streamFragmentsIf(fn () => request()->isDatastar(), ['counter']);
```

To work with rendered fragments yourself, `fragmentsAsCollection` returns them as a `Collection` — all fragments of the view, or only the named ones:

```php
view('todos.index')->fragmentsAsCollection();            // every fragment, keyed by name
view('todos.index')->fragmentsAsCollection(['list']);    // only the named fragments
```

## Precognition

[Laravel Precognition](https://laravel.com/docs/precognition) lets you run a request's validation rules *without* executing the controller — perfect for live validation while the user types. Because `DatastarRequest` is a regular `FormRequest`, it works with Precognition out of the box: add the `HandlePrecognitiveRequests` middleware to the route, and reuse the same request class for both the precognitive check and the real submission.

```php
use Illuminate\Foundation\Http\Middleware\HandlePrecognitiveRequests;

Route::put('/profile', function (UpdateProfileRequest $request) {
    // only reached by real submissions, never by precognitive requests
})->middleware([HandlePrecognitiveRequests::class]);
```

On the Datastar side, no Precognition frontend package is required — a small form component covers everything. Submitting with `contentType: 'form'` sends the actual form fields (regular Laravel input), and the debounced `input` listener re-posts the form with the `Precognition` headers as the user types, validating only the field they are typing in via `evt.target.name`:

```blade
{{-- resources/views/components/form.blade.php --}}
@props(['live' => false])

<form
    method="POST"
    data-signals="{errors: {}}"
    data-on:submit="@post(el.action, {contentType: 'form'})"
    @if ($live)
        data-on:input__debounce.400ms="@post(el.action, {
            contentType: 'form',
            headers: {
                'Accept': 'application/json',
                'Precognition': 'true',
                'Precognition-Validate-Only': evt.target.name,
            },
        })"
    @endif
    {{ $attributes }}
>
    @csrf
    {{ $slot }}
</form>
```

The `Accept: application/json` header matters: it makes failed precognitive checks respond with a `422` JSON error payload instead of a redirect. A passing check responds `204` with a `Precognition-Success: true` header. Real submissions (without the `Precognition` header) run through validation and your controller exactly as before.

Since `contentType: 'form'` submits ordinary form input rather than signals, your rules read it like any Laravel request — this works identically with a plain `FormRequest` or a `DatastarRequest`. If you drive the form through signals instead (`data-bind`), send the same `Precognition` headers with a regular Datastar action and `DatastarRequest` validates the signal payload precognitively.

### Displaying validation errors

Datastar applies JSON responses to signals, so the `errors` object from Laravel's `422` payload lands in the `$errors` signal the form initialized. Displaying an error next to its field is a one-liner component — Laravel's errors are arrays per field, so `.0` reads the first message:

```blade
{{-- resources/views/components/form/error.blade.php --}}
@props(['name'])

<p
    style="display: none"
    data-show="$errors.{{ $name }}"
    data-text="$errors.{{ $name }} && $errors.{{ $name }}.0"
    {{ $attributes }}
></p>
```

```blade
<x-form action="/profile" live>
    <input name="name" />
    <x-form.error name="name" class="text-sm text-red-600" />

    <input name="email" type="email" />
    <x-form.error name="email" class="text-sm text-red-600" />

    <button>Save</button>
</x-form>
```

## Broadcasting

To push Datastar patches over websockets, add one of the broadcast traits to a Laravel event. Each trait implements `broadcastWith()` and `broadcastAs()` for you and asks only for the event-specific input:

| Trait | You implement | Optional overrides |
| --- | --- | --- |
| `IsPatchElementsEvent` | `elements(): string\|View` | — |
| `IsPatchSignalsEvent` | `signals(): array` | `onlyIfMissing(): ?bool` |
| `IsRemoveElementsEvent` | `selector(): string` | — |
| `IsExecuteScriptEvent` | `script(): string` | `autoRemove(): bool`, `attributes(): array` |
| `IsLocationEvent` | `uri(): string` | — |

```php
use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\View\View;
use Suenerds\LaravelDatastar\IsPatchElementsEvent;

class OrderShipped implements ShouldBroadcast
{
    use IsPatchElementsEvent;

    public function __construct(public Order $order) {}

    public function elements(): View
    {
        return view('orders.status', ['order' => $this->order]);
    }

    public function broadcastOn(): Channel
    {
        return new Channel('orders.'.$this->order->id);
    }
}
```

The event broadcasts as `datastar-patch-elements` (or `datastar-patch-signals` for the signals trait) with the rendered patch as its payload, ready for a Datastar client listening on the channel.

## Testing

```bash
composer test
```

Runs static analysis (PHPStan level 7), formatting checks (Pint), type coverage, and the Pest test suite.

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Thank you for considering contributing to Laravel Datastar! Please review our [contributing guide](.github/CONTRIBUTING.md) to get started.

## Security Vulnerabilities

Please review [our security policy](.github/SECURITY.md) on how to report security vulnerabilities.

## Credits

- [Thore Sünert](https://github.com/suenerds)
- [All Contributors](../../contributors)

## License

Laravel Datastar is open-sourced software licensed under the [MIT license](LICENSE.md).
