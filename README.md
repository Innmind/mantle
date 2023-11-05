# Mantle

[![Build Status](https://github.com/innmind/mantle/workflows/CI/badge.svg?branch=master)](https://github.com/innmind/mantle/actions?query=workflow%3ACI)
[![codecov](https://codecov.io/gh/innmind/mantle/branch/develop/graph/badge.svg)](https://codecov.io/gh/innmind/mantle)
[![Type Coverage](https://shepherd.dev/github/innmind/mantle/coverage.svg)](https://shepherd.dev/github/innmind/mantle)

Abstraction on top of `Fiber`s to coordinate multiple tasks asynchronously.

The goal is to easily move the execution of any code built using [`innmind/operating-system`](https://packagist.org/packages/innmind/operating-system) from a synchronous context to an async one. This means that it's easier to experiment running a piece of code asynchronously and then move back if the experiment is not successful. This also means that you can test each part of an asynchronous system synchronously.

## Installation

```sh
composer require innmind/mantle
```

## Usage

```php
use Innmind\Mantle\{
    Forerunner,
    Task,
    Source\Continuation,
};
use Innmind\OperatingSystem\{
    Factory,
    OperatingSystem,
};
use Innmind\Filesystem\Name;
use Innmind\HttpTransport\Success;
use Innmind\Http\{
    Request,
    Method,
    ProtocolVersion,
};
use Innmind\Url\{
    Url,
    Path,
};
use Innmind\Immutable\Sequence;

$run = Forerunner::of(Factory::build());
[$users] = $run(
    [0, 0, false],
    static function(array $carry, OperatingSystem $os, Continuation $continuation, Sequence $results): Continuation {
        [$users, $finished, $launched] = $carry;

        if (!$launched) {
            return $continuation
                ->carryWith([$users, $finished, true])
                ->launch(Sequence::of(
                    Task::of(
                        static fn(OperatingSystem $os): int => $os
                            ->remote()
                            ->http()(Request::of(
                                Url::of('http://some-service.tld/users/count'),
                                Method::get,
                                ProtocolVersion::v11,
                            ))
                            ->map(static fn(Success $success): string => $success->response()->body()->toString())
                            ->match(
                                static fn(string $response): int => (int) $response,
                                static fn() => throw new \RuntimeException('Failed to count the users'),
                            ),
                    ),
                    Task::of(
                        static fn(OperatingSystem $os): int => $os
                            ->filesystem()
                            ->mount(Path::of('some/directory/'))
                            ->get(Name::of('users.csv'))
                            ->map(static fn($file) => $file->content()->lines())
                            ->match(
                                static fn(Sequence $lines) => $lines->reduce(
                                    0,
                                    static fn(int $total): int => $total + 1,
                                ),
                                static fn() => throw new \RuntimeException('Users file not found'),
                            ),
                    ),
                ));
        }

        $finished += $results->size();
        $users = $results->reduce(
            $users,
            static fn(int $total, int $result): int => $total + $result,
        );
        $continuation = $continuation->carryWith([$users, $finished, $launched]);

        if ($finished === 2) {
            $continuation = $continuation->terminate();
        }

        return $continuation;
    },
);
```

This example count a number of `$users` coming from 2 sources.

The `Forerunner` object behaves as a _reduce_ operation, that's why it has 2 arguments: a carried value and a reducer (called a source in this package).

The carried value here is an array that holds the number of fetched users, the number of finished tasks and whether it already launched the tasks or not.

The source will launch 2 tasks if not already done; the first one does an HTTP call and the second one counts the number of lines in a file. The source will be called again once a task finishes and their results will be available inside the fourth argument `$results`, it will add the number of finished tasks and the number of users to the carried value array. If both tasks are finished then the source calls `$continuation->terminate()` to instruct the loop to stop.

When the source calls `->terminate()` and that all tasks are finished then `$run()` returns the carried value. Here it will assign the aggregation of both tasks results to the value `$users`.

## Limitations

### Signals

Signals like `SIGINT`, `SIGTERM`, etc... that are normally handled via `$os->process()->signals()` is not yet supported. This may result in unwanted behaviours.

### HTTP calls

Currently HTTP calls are done via `curl` but it can't be integrated in the same loop as other streams. To allow the coordination of multiple tasks when doing HTTP calls the system use a timeout of `10ms` and switches between tasks at this max rate.

To fix this limitation a new implementation entirely based on PHP streams needs to be created.

Meanwhile if your goal is to make multiple concurrent HTTP calls you don't need this package. [`innmind/http-transport`](https://packagist.org/packages/innmind/http-transport) already support concurrent calls on it's own (without the limitation mentionned above).

### SQL queries

SQL queries executed via `$os->remote()->sql()` are still executed synchronously.

To fix this limitation a new implementation entirely based on PHP streams needs to be created.

### Number of tasks

It seems that the current implementation of this package has a [limit of around 10K concurrent tasks](https://twitter.com/baptouuuu/status/1720092619496378741) before it starts slowing down drastically.
