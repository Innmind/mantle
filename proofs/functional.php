<?php
declare(strict_types = 1);

use Innmind\Mantle\{
    Forerunner,
    Source\Predetermined,
    Task,
};
use Innmind\OperatingSystem\Factory;
use Innmind\TimeContinuum\Earth\Period\Second;
use Innmind\Filesystem\Name;
use Innmind\Url\Path;
use Innmind\Immutable\Sequence;
use Innmind\BlackBox\Set;
use Innmind\BlackBox\Tag;

return static function() {
    yield test(
        'Halting multiple tasks',
        static function($assert) {
            $expect = $assert->time(static function() {
                Forerunner::of(Factory::build())(null, Predetermined::of(
                    static fn($os) => $os->process()->halt(Second::of(1)),
                    static fn($os) => $os->process()->halt(Second::of(1)),
                    static fn($os) => $os->process()->halt(Second::of(1)),
                ));
            });
            $expect
                ->inLessThan()
                ->seconds(2);
            $expect
                ->inMoreThan()
                ->seconds(1);
        },
    );

    yield proof(
        'Carry value via the source',
        given(
            Set\Type::any(),
            Set\Type::any(),
        ),
        static function($assert, $initial, $modified) {
            $returned = Forerunner::of(Factory::build())(
                $initial,
                static fn($_, $__, $continuation) => $continuation->terminate(),
            );
            $assert->same($initial, $returned);

            $returned = Forerunner::of(Factory::build())(
                $initial,
                static fn($carry, $__, $continuation) => $continuation
                    ->carryWith($initial)
                    ->terminate(),
            );
            $assert->same($initial, $returned);

            $returned = Forerunner::of(Factory::build())(
                $initial,
                static fn($carry, $__, $continuation) => $continuation
                    ->carryWith($modified)
                    ->terminate(),
            );
            $assert->same($modified, $returned);
        },
    );

    yield test(
        'The source is run asynchronously',
        static function($assert) {
            $expect = $assert->time(static function() {
                Forerunner::of(Factory::build())(
                    false,
                    static fn($started, $os, $continuation, $results) => match ([$started, $results->size()]) {
                        [false, 0] => $continuation
                            ->launch(Sequence::of(
                                Task::of(static function($os) {
                                    $os->process()->halt(Second::of(1));
                                    $os->process()->halt(Second::of(1));
                                }),
                                Task::of(static function($os) {
                                    $os->process()->halt(Second::of(1));
                                    $os->process()->halt(Second::of(1));
                                }),
                                Task::of(static function($os) {
                                    $os->process()->halt(Second::of(1));
                                    $os->process()->halt(Second::of(1));
                                }),
                            ))
                            ->carryWith(true),
                        [true, 0] => (static function($os, $continuation) {
                            // this halt is executed at the same time at the
                            // second one in each task
                            $os->process()->halt(Second::of(1));

                            return $continuation;
                        })($os, $continuation),
                        default => $continuation->terminate(),
                    },
                );
            });
            $expect
                ->inLessThan()
                ->seconds(3);
            $expect
                ->inMoreThan()
                ->seconds(2);
        },
    );

    yield test(
        'Streams are handled asynchronously',
        static function($assert) {
            $lines = [];
            Forerunner::of(Factory::build())(null, Predetermined::of(
                static function($os) use ($assert, &$lines) {
                    $file = $os
                        ->filesystem()
                        ->mount(Path::of('./'))
                        ->get(Name::of('composer.json'))
                        ->match(
                            static fn($file) => $file,
                            static fn() => null,
                        );
                    $assert->not()->null($file);
                    $lines[] = $file
                        ->content()
                        ->lines()
                        ->first()
                        ->match(
                            static fn($line) => $line->toString(),
                            static fn() => null,
                        );
                    $lines[] = $file
                        ->content()
                        ->lines()
                        ->filter(static fn($line) => !$line->str()->empty())
                        ->last()
                        ->match(
                            static fn($line) => $line->toString(),
                            static fn() => null,
                        );
                },
                static function($os) use ($assert, &$lines) {
                    $file = $os
                        ->filesystem()
                        ->mount(Path::of('./'))
                        ->get(Name::of('LICENSE'))
                        ->match(
                            static fn($file) => $file,
                            static fn() => null,
                        );
                    $assert->not()->null($file);
                    $lines[] = $file
                        ->content()
                        ->lines()
                        ->first()
                        ->match(
                            static fn($line) => $line->toString(),
                            static fn() => null,
                        );
                    $lines[] = $file
                        ->content()
                        ->lines()
                        ->filter(static fn($line) => !$line->str()->empty())
                        ->last()
                        ->match(
                            static fn($line) => $line->toString(),
                            static fn() => null,
                        );
                },
            ));
            $assert->same(
                ['{', 'MIT License', 'SOFTWARE.', '}'],
                $lines,
            );
        },
    )->tag(Tag::wip);
};
