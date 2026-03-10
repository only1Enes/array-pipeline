<?php

declare(strict_types=1);

namespace ArrayPipeline;

use ArrayIterator;
use ArrayPipeline\Contracts\PipelineInterface;
use ArrayPipeline\Exceptions\PipelineException;
use TypeError;

class Pipeline implements PipelineInterface
{
    /**
     * @var array<mixed>
     */
    private array $items;

    /**
     * @param iterable<mixed> $data
     * @return void
     */
    public function __construct(iterable $data)
    {
        $this->items = is_array($data) ? $data : iterator_to_array($data);
    }

    /**
     * @param iterable<mixed> $data
     * @return static
     */
    public static function from(iterable $data): static
    {
        return new static($data instanceof \Traversable ? iterator_to_array($data) : $data);
    }

    /**
     * @param callable $fn
     * @return static
     */
    public function map(callable $fn): static
    {
        $this->items = array_map($fn, $this->items);

        return $this;
    }

    /**
     * @param callable $fn
     * @return static
     */
    public function filter(callable $fn): static
    {
        $this->items = array_values(array_filter($this->items, $fn, ARRAY_FILTER_USE_BOTH));

        return $this;
    }

    /**
     * @param callable $fn
     * @return static
     */
    public function reject(callable $fn): static
    {
        $this->items = array_values(
            array_filter(
                $this->items,
                fn (mixed $item, int|string $index): bool => !$fn($item, $index),
                ARRAY_FILTER_USE_BOTH
            )
        );

        return $this;
    }

    /**
     * @param callable $fn
     * @param mixed $initial
     * @return mixed
     */
    public function reduce(callable $fn, mixed $initial = null): mixed
    {
        return array_reduce($this->items, $fn, $initial);
    }

    /**
     * @param callable|string $key
     * @return static
     * @throws PipelineException
     */
    public function groupBy(callable|string $key): static
    {
        $grouped = [];

        foreach ($this->items as $item) {
            if (is_string($key)) {
                if (!is_array($item) || !array_key_exists($key, $item)) {
                    throw PipelineException::keyNotFound($key);
                }

                $groupKey = $item[$key];
            } else {
                $groupKey = $key($item);
            }

            $grouped[$this->normalizeArrayKey($groupKey)][] = $item;
        }

        $this->items = $grouped;

        return $this;
    }

    /**
     * @param int $size
     * @return static
     * @throws PipelineException
     */
    public function chunk(int $size): static
    {
        if ($size <= 0) {
            throw PipelineException::invalidChunkSize($size);
        }

        $this->items = array_chunk($this->items, $size);

        return $this;
    }

    /**
     * @param int $depth
     * @return static
     */
    public function flatten(int $depth = 1): static
    {
        $this->items = $this->flattenArray($this->items, $depth);

        return $this;
    }

    /**
     * @param callable|null $fn
     * @return static
     */
    public function sort(?callable $fn = null): static
    {
        $items = array_values($this->items);

        if ($fn === null) {
            sort($items);
        } else {
            usort($items, $fn);
        }

        $this->items = $items;

        return $this;
    }

    /**
     * @param string $key
     * @param string $direction
     * @return static
     * @throws PipelineException
     */
    public function sortBy(string $key, string $direction = 'asc'): static
    {
        $items = array_values($this->items);
        $direction = strtolower($direction) === 'desc' ? 'desc' : 'asc';

        foreach ($items as $item) {
            if (!is_array($item) || !array_key_exists($key, $item)) {
                throw PipelineException::keyNotFound($key);
            }
        }

        usort(
            $items,
            fn (array $a, array $b): int => $direction === 'asc'
                ? ($a[$key] <=> $b[$key])
                : ($b[$key] <=> $a[$key])
        );

        $this->items = $items;

        return $this;
    }

    /**
     * @param callable|null $fn
     * @return static
     */
    public function unique(?callable $fn = null): static
    {
        if ($fn === null) {
            $this->items = array_values(array_unique($this->items, SORT_REGULAR));

            return $this;
        }

        $seen = [];
        $result = [];

        foreach ($this->items as $item) {
            $value = $fn($item);
            $hash = $this->normalizeUniqueKey($value);

            if (array_key_exists($hash, $seen)) {
                continue;
            }

            $seen[$hash] = true;
            $result[] = $item;
        }

        $this->items = $result;

        return $this;
    }

    /**
     * @param int $n
     * @return static
     */
    public function take(int $n): static
    {
        $this->items = array_slice($this->items, 0, max(0, $n));

        return $this;
    }

    /**
     * @param int $n
     * @return static
     */
    public function skip(int $n): static
    {
        $this->items = array_slice($this->items, max(0, $n));

        return $this;
    }

    /**
     * @param callable $fn
     * @return static
     */
    public function tap(callable $fn): static
    {
        foreach ($this->items as $item) {
            $fn($item);
        }

        return $this;
    }

    /**
     * @param callable $fn
     * @return static
     * @throws TypeError
     */
    public function pipe(callable $fn): static
    {
        $result = $fn($this->items);

        if (!is_array($result)) {
            throw new TypeError('Pipeline::pipe callback must return an array.');
        }

        $this->items = $result;

        return $this;
    }

    /**
     * @param string|object $class
     * @return static
     * @throws PipelineException
     * @throws TypeError
     */
    public function through(string|object $class): static
    {
        $instance = $class;

        if (is_string($class)) {
            if (!class_exists($class)) {
                throw PipelineException::notInvokable($class);
            }

            $instance = new $class();
        }

        if (!is_object($instance) || !is_callable($instance)) {
            $name = is_object($instance) ? $instance::class : (string) $class;
            throw PipelineException::notInvokable($name);
        }

        return $this->pipe(fn (array $items): array => $instance($items));
    }

    /**
     * @param array<mixed> ...$arrays
     * @return static
     */
    public function zip(array ...$arrays): static
    {
        $sources = [array_values($this->items), ...array_map('array_values', $arrays)];
        $this->items = array_map(null, ...$sources);

        return $this;
    }

    /**
     * @param array<mixed> ...$arrays
     * @return static
     */
    public function merge(array ...$arrays): static
    {
        $this->items = array_merge($this->items, ...$arrays);

        return $this;
    }

    /**
     * @param callable|string $key
     * @return static
     */
    public function keyBy(callable|string $key): static
    {
        $result = [];

        foreach ($this->items as $item) {
            if (is_string($key)) {
                $keyValue = is_array($item) && array_key_exists($key, $item) ? $item[$key] : null;
            } else {
                $keyValue = $key($item);
            }

            $result[$this->normalizeArrayKey($keyValue)] = $item;
        }

        $this->items = $result;

        return $this;
    }

    /**
     * @param string $key
     * @return static
     */
    public function pluck(string $key): static
    {
        $this->items = array_map(
            fn (mixed $item): mixed => is_array($item) && array_key_exists($key, $item) ? $item[$key] : null,
            $this->items
        );

        return $this;
    }

    /**
     * @param callable $fn
     * @return static
     */
    public function each(callable $fn): static
    {
        foreach ($this->items as $index => $item) {
            $fn($item, $index);
        }

        return $this;
    }

    /**
     * @param bool $condition
     * @param callable $then
     * @param callable|null $otherwise
     * @return static
     */
    public function when(bool $condition, callable $then, ?callable $otherwise = null): static
    {
        if ($condition) {
            $result = $then($this);

            return $result instanceof static ? $result : $this;
        }

        if ($otherwise !== null) {
            $result = $otherwise($this);

            return $result instanceof static ? $result : $this;
        }

        return $this;
    }

    /**
     * @param bool $condition
     * @param callable $fn
     * @return static
     */
    public function unless(bool $condition, callable $fn): static
    {
        return $this->when(!$condition, $fn);
    }

    /**
     * @return static
     */
    public function compact(): static
    {
        $this->items = array_values(
            array_filter(
                $this->items,
                fn (mixed $item): bool => $item !== null && $item !== false && $item !== '' && $item !== []
            )
        );

        return $this;
    }

    /**
     * @return static
     */
    public function flip(): static
    {
        $this->items = array_flip($this->items);

        return $this;
    }

    /**
     * @return array<mixed>
     */
    public function toArray(): array
    {
        return $this->items;
    }

    /**
     * @param int $flags
     * @return string
     */
    public function toJson(int $flags = 0): string
    {
        $json = json_encode($this->items, $flags);

        return $json === false ? '[]' : $json;
    }

    /**
     * @param callable|null $fn
     * @return mixed
     */
    public function first(?callable $fn = null): mixed
    {
        if ($fn === null) {
            foreach ($this->items as $item) {
                return $item;
            }

            return null;
        }

        foreach ($this->items as $index => $item) {
            if ($fn($item, $index)) {
                return $item;
            }
        }

        return null;
    }

    /**
     * @param callable|null $fn
     * @return mixed
     */
    public function last(?callable $fn = null): mixed
    {
        if ($fn === null) {
            if ($this->isEmpty()) {
                return null;
            }

            $items = array_values($this->items);

            return $items[count($items) - 1];
        }

        $match = null;
        $found = false;

        foreach ($this->items as $index => $item) {
            if ($fn($item, $index)) {
                $match = $item;
                $found = true;
            }
        }

        return $found ? $match : null;
    }

    /**
     * @return int
     */
    public function count(): int
    {
        return count($this->items);
    }

    /**
     * @param callable|string|null $key
     * @return int|float
     */
    public function sum(string|callable|null $key = null): int|float
    {
        $sum = 0;

        foreach ($this->resolveValues($key) as $value) {
            if (is_int($value) || is_float($value) || is_numeric($value)) {
                $sum += $value + 0;
            }
        }

        return $sum;
    }

    /**
     * @param callable|string|null $key
     * @return float
     */
    public function avg(string|callable|null $key = null): float
    {
        $values = array_values(
            array_filter(
                $this->resolveValues($key),
                fn (mixed $value): bool => is_int($value) || is_float($value) || is_numeric($value)
            )
        );

        if ($values === []) {
            return 0.0;
        }

        return (float) (array_sum($values) / count($values));
    }

    /**
     * @param callable|string|null $key
     * @return mixed
     */
    public function min(string|callable|null $key = null): mixed
    {
        $values = $this->resolveValues($key);

        if ($values === []) {
            return null;
        }

        $min = array_shift($values);

        foreach ($values as $value) {
            if ($value < $min) {
                $min = $value;
            }
        }

        return $min;
    }

    /**
     * @param callable|string|null $key
     * @return mixed
     */
    public function max(string|callable|null $key = null): mixed
    {
        $values = $this->resolveValues($key);

        if ($values === []) {
            return null;
        }

        $max = array_shift($values);

        foreach ($values as $value) {
            if ($value > $max) {
                $max = $value;
            }
        }

        return $max;
    }

    /**
     * @param mixed $value
     * @return bool
     */
    public function contains(mixed $value): bool
    {
        if (is_callable($value)) {
            foreach ($this->items as $index => $item) {
                if ($value($item, $index)) {
                    return true;
                }
            }

            return false;
        }

        return in_array($value, $this->items, true);
    }

    /**
     * @param callable $fn
     * @return bool
     */
    public function every(callable $fn): bool
    {
        foreach ($this->items as $index => $item) {
            if (!$fn($item, $index)) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param callable $fn
     * @return bool
     */
    public function some(callable $fn): bool
    {
        foreach ($this->items as $index => $item) {
            if ($fn($item, $index)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return bool
     */
    public function isEmpty(): bool
    {
        return $this->items === [];
    }

    /**
     * @return bool
     */
    public function isNotEmpty(): bool
    {
        return !$this->isEmpty();
    }

    /**
     * @return static
     */
    public function dump(): static
    {
        var_dump($this->items);

        return $this;
    }

    /**
     * @return never
     */
    public function dd(): never
    {
        var_dump($this->items);
        exit(1);
    }

    /**
     * @return ArrayIterator<int|string, mixed>
     */
    public function getIterator(): ArrayIterator
    {
        return new ArrayIterator($this->items);
    }

    /**
     * @return mixed
     */
    public function jsonSerialize(): mixed
    {
        return $this->items;
    }

    /**
     * @param array<mixed> $items
     * @param int $depth
     * @return array<mixed>
     */
    private function flattenArray(array $items, int $depth): array
    {
        if ($depth < 1) {
            return $items;
        }

        $result = [];

        foreach ($items as $item) {
            if (is_array($item)) {
                $values = $this->flattenArray($item, $depth - 1);
                foreach ($values as $value) {
                    $result[] = $value;
                }
            } else {
                $result[] = $item;
            }
        }

        return $result;
    }

    /**
     * @param callable|string|null $key
     * @return array<mixed>
     */
    private function resolveValues(callable|string|null $key): array
    {
        if ($key === null) {
            return array_values($this->items);
        }

        if (is_callable($key)) {
            return array_values(array_map($key, $this->items));
        }

        $values = [];

        foreach ($this->items as $item) {
            $values[] = is_array($item) && array_key_exists($key, $item) ? $item[$key] : null;
        }

        return $values;
    }

    /**
     * @param mixed $value
     * @return int|string
     */
    private function normalizeArrayKey(mixed $value): int|string
    {
        if (is_int($value) || is_string($value)) {
            return $value;
        }

        if (is_bool($value)) {
            return (int) $value;
        }

        if (is_float($value)) {
            return (string) $value;
        }

        if ($value === null) {
            return '';
        }

        if (is_object($value)) {
            return spl_object_id($value);
        }

        return json_encode($value) ?: '';
    }

    /**
     * @param mixed $value
     * @return string
     */
    private function normalizeUniqueKey(mixed $value): string
    {
        if (is_object($value)) {
            return 'object:' . spl_object_id($value);
        }

        if (is_resource($value)) {
            return 'resource:' . get_resource_id($value);
        }

        return gettype($value) . ':' . serialize($value);
    }
}
