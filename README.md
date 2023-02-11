# Mantle

[![Build Status](https://github.com/innmind/mantle/workflows/CI/badge.svg?branch=master)](https://github.com/innmind/mantle/actions?query=workflow%3ACI)
[![codecov](https://codecov.io/gh/innmind/mantle/branch/develop/graph/badge.svg)](https://codecov.io/gh/innmind/mantle)
[![Type Coverage](https://shepherd.dev/github/innmind/mantle/coverage.svg)](https://shepherd.dev/github/innmind/mantle)

Minimalist abstraction on top of `Fiber`s to coordinate multiple tasks asynchronously.

This package is intended for other packages to be built upon, end developers should not directly face this abstraction.

## Installation

```sh
composer require innmind/mantle
```

## Concepts

### Source

A `Source` let _emerge_ `Task`s that needs to be run asynchronously. For example a web server can be a `Source` that will emerge a `Task` when a new connection is received.

This packages comes with the following sources:
- `Predetermined`: accepts a list of `callable`s that can be suspended
- `Throttle`: limits the number of `Task`s that can be run

A `Source` has a notion of `active` to instruct the `Forerunner` if there will be other tasks in the future or not.

### Task

A `Task` takes a `callable` that will be run asynchronously. For the `callable` to effectively run asynchronously it must yield control of the process via the `Suspend` object passed as an argument to it.

### Suspend

`Suspend` is an object on top of [`Fiber`s](https://www.php.net/manual/en/language.fibers.php) that is only accessible to a `Task` created by a `Source`. When called the `Task` will yield control to allow other `Task`s to run.

The `Suspend` behaviour can be changed with different strategies:
- `Asynchornous` will yield as soon as `Suspend` is called (default)
- `Synchronous` will yield only when the `Task` is finished, meaning it reached the end of the `callable`
- `TimeFrame` will yield control when it exceeds the time frame allowed for a `Task` to run

### Forerunner

This is the main object that will coordinate all the objects above. It operates as a _reduce like_ operation with a _carried value_ and a `Source` acting as the list `Task`s to reduce. The carried value is accessible everytime the `Source` is called to provide `Task`s.

When the `Source` is no longer active and there is no more `Task`s to run the object will return.
