# Changelog

## [Unreleased]

### Fixed

- Wait less time to restart the source by polling the tasks instead of waiting for them to be reactivated
- Takes less time and memory to compute the timeout and streams to watch

## 2.0.0 - 2023-11-05

### Added

- `Innmind\Mantle\Source\Continuation`

### Changed

- Requires `innmind/operating-system:~4.1`
- `Innmind\Mantle\Forerunner::of()` now only accepts an instance of `Innmind\OperatingSystem\OperatingSystem`
- `Innmind\Mantle\Forerunner::__invoke()` second argument now is a `callable` (previously it was a `Source`)
- `Innmind\Mantle\Suspend` is now declared internal
- `Innmind\Mantle\Source` is now declared internal
- `Innmind\Mantle\Source\Predetermined::of()` `callable`s now receive an instance of `Innmind\OperatingSystem\OperatingSystem`

### Removed

- `Innmind\Mantle\Source\Throttle`
- `Innmind\Mantle\Suspend\Strategy`
- `Innmind\Mantle\Suspend\Asynchronous`
- `Innmind\Mantle\Suspend\Synchronous`
- `Innmind\Mantle\Suspend\TimeFrame`

## 1.1.0 - 2023-09-16

### Added

- Support for `innmind/immutable` `5`

### Removed

- Support for PHP `8.1`
