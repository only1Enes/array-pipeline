<?php

declare(strict_types=1);

namespace ArrayPipeline\Contracts;

use ArrayIterator;
use Countable;
use IteratorAggregate;
use JsonSerializable;

interface PipelineInterface extends Countable, IteratorAggregate, JsonSerializable
{
    /**
     * @param iterable<mixed> $data
     * @return static
     */
    public static function from(iterable $data): static;

    /**
     * @param callable $fn
     * @return static
     */
    public function map(callable $fn): static;

    /**
     * @param callable $fn
     * @return static
     */
    public function filter(callable $fn): static;

    /**
     * @param callable $fn
     * @return static
     */
    public function reject(callable $fn): static;

    /**
     * @param callable $fn
     * @param mixed $initial
     * @return mixed
     */
    public function reduce(callable $fn, mixed $initial = null): mixed;

    /**
     * @param callable|string $key
     * @return static
     */
    public function groupBy(callable|string $key): static;

    /**
     * @param int $size
     * @return static
     */
    public function chunk(int $size): static;

    /**
     * @param int $depth
     * @return static
     */
    public function flatten(int $depth = 1): static;

    /**
     * @param callable|null $fn
     * @return static
     */
    public function sort(?callable $fn = null): static;

    /**
     * @param string $key
     * @param string $direction
     * @return static
     */
    public function sortBy(string $key, string $direction = 'asc'): static;

    /**
     * @param callable|null $fn
     * @return static
     */
    public function unique(?callable $fn = null): static;

    /**
     * @param int $n
     * @return static
     */
    public function take(int $n): static;

    /**
     * @param int $n
     * @return static
     */
    public function skip(int $n): static;

    /**
     * @param callable $fn
     * @return static
     */
    public function tap(callable $fn): static;

    /**
     * @param callable $fn
     * @return static
     */
    public function pipe(callable $fn): static;

    /**
     * @param string|object $class
     * @return static
     */
    public function through(string|object $class): static;

    /**
     * @param array<mixed> ...$arrays
     * @return static
     */
    public function zip(array ...$arrays): static;

    /**
     * @param array<mixed> ...$arrays
     * @return static
     */
    public function merge(array ...$arrays): static;

    /**
     * @param callable|string $key
     * @return static
     */
    public function keyBy(callable|string $key): static;

    /**
     * @param string $key
     * @return static
     */
    public function pluck(string $key): static;

    /**
     * @param callable $fn
     * @return static
     */
    public function each(callable $fn): static;

    /**
     * @param bool $condition
     * @param callable $then
     * @param callable|null $otherwise
     * @return static
     */
    public function when(bool $condition, callable $then, ?callable $otherwise = null): static;

    /**
     * @param bool $condition
     * @param callable $fn
     * @return static
     */
    public function unless(bool $condition, callable $fn): static;

    /**
     * @return static
     */
    public function compact(): static;

    /**
     * @return static
     */
    public function flip(): static;

    /**
     * @return array<mixed>
     */
    public function toArray(): array;

    /**
     * @param int $flags
     * @return string
     */
    public function toJson(int $flags = 0): string;

    /**
     * @param callable|null $fn
     * @return mixed
     */
    public function first(?callable $fn = null): mixed;

    /**
     * @param callable|null $fn
     * @return mixed
     */
    public function last(?callable $fn = null): mixed;

    /**
     * @return int
     */
    public function count(): int;

    /**
     * @param callable|string|null $key
     * @return int|float
     */
    public function sum(string|callable|null $key = null): int|float;

    /**
     * @param callable|string|null $key
     * @return float
     */
    public function avg(string|callable|null $key = null): float;

    /**
     * @param callable|string|null $key
     * @return mixed
     */
    public function min(string|callable|null $key = null): mixed;

    /**
     * @param callable|string|null $key
     * @return mixed
     */
    public function max(string|callable|null $key = null): mixed;

    /**
     * @param mixed $value
     * @return bool
     */
    public function contains(mixed $value): bool;

    /**
     * @param callable $fn
     * @return bool
     */
    public function every(callable $fn): bool;

    /**
     * @param callable $fn
     * @return bool
     */
    public function some(callable $fn): bool;

    /**
     * @return bool
     */
    public function isEmpty(): bool;

    /**
     * @return bool
     */
    public function isNotEmpty(): bool;

    /**
     * @return static
     */
    public function dump(): static;

    /**
     * @return never
     */
    public function dd(): never;

    /**
     * @return ArrayIterator<int|string, mixed>
     */
    public function getIterator(): ArrayIterator;

    /**
     * @return mixed
     */
    public function jsonSerialize(): mixed;
}
