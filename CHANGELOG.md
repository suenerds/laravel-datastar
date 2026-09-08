# Release Notes

## [Unreleased](https://github.com/suenerds/laravel-datastar/compare/v0.1.0...1.x)

### Added

- `IsPatchSignalsEvent`, `IsRemoveElementsEvent`, `IsExecuteScriptEvent`, and `IsLocationEvent` broadcast traits, completing the set alongside `IsPatchElementsEvent` so host-app events can broadcast any Datastar event type.
- `PatchSignals::onlyIfMissing()` for the serialized `true`/absent representation of the only-if-missing flag.
- Test suite covering `DatastarRequest` validation, the streamed event classes and their `toResponse()` paths, the `sse`, `signals`, `isDatastar`, `fragmentsAsCollection`, and `streamFragmentsIf` macros, and the broadcast traits.
- README documentation covering the full package surface: signal reading and validation, the event classes and both response styles, Blade fragment streaming, and the broadcast traits. The obsolete `vendor:publish` instructions were removed since the package has no publishable resources.

### Changed

- Requires PHP 8.4 (the streamed event classes rely on property hooks).
- Renamed the `IsPatchEvent` trait to `IsPatchElementsEvent`.
- `IsPatchElementsEvent::elements()` is now abstract with a `string|View` return type; the previous default returned an array that `PatchElements` rejects with a `TypeError`, so the trait was unusable without an override.
- `PatchSignals::toResponse()` is typed to return the `JsonResponse` it always produced.
- Package source passes PHPStan level 7 with full generics and 100% native type coverage; `composer analyse` and `composer test:types` run with a 1G memory limit so they no longer crash at PHP's default.

### Fixed

- `PatchSignals` SSE events now emit protocol-correct, key-prefixed data lines (`signals {...}` and `onlyIfMissing true`) instead of bare values, and the `datastar-only-if-missing` response header sends `true` instead of `1`.
- `PatchSignals` throws a `JsonException` when the signals cannot be JSON-encoded instead of silently dropping the `signals` line from the event.
- `PatchElements` no longer references an undefined variable when constructed without `elements`.
- `View::fragmentsAsCollection()` collects fragments without misusing the `render()` callback return value.
- Test scaffolding, autoloading, and the architecture test reference the `Suenerds\LaravelDatastar` namespace; the suite failed to boot after the package rename.


## [v0.1.0](https://github.com/suenerds/laravel-datastar/compare/...v0.1.0) - 202x-xx-xx

Initial pre-release.
