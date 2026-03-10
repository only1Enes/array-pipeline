# ArrayPipeline

[![PHP Version](https://img.shields.io/badge/PHP-8.1%2B-777BB4.svg)](https://www.php.net/)
[![License](https://img.shields.io/badge/license-MIT-green.svg)](LICENSE)

Fluent, chainable array pipeline for PHP 8.1+ with zero dependencies.

## Installation

```bash
composer require arraypipeline/core
```

## Quick Example

```php
<?php

use ArrayPipeline\Pipeline;

$report = Pipeline::from($orders)
    ->filter(fn (array $order): bool => $order['status'] === 'paid')
    ->groupBy('country')
    ->map(fn (array $countryOrders): array => Pipeline::from($countryOrders)
        ->groupBy('customer_id')
        ->map(fn (array $customerOrders): array => [
            'orders' => count($customerOrders),
            'total' => Pipeline::from($customerOrders)->sum('amount'),
        ])
        ->toArray())
    ->toArray();
```

## API

| Method | Signature | Description |
|---|---|---|
| `from` | `from(iterable $data): static` | Create a pipeline instance from iterable data. |
| `__construct` | `__construct(iterable $data)` | Initialize pipeline from iterable data. |
| `map` | `map(callable $fn): static` | Transform each item and return a new pipeline. |
| `filter` | `filter(callable $fn): static` | Keep items that match callback. |
| `reject` | `reject(callable $fn): static` | Remove items that match callback. |
| `reduce` | `reduce(callable $fn, mixed $initial = null): mixed` | Reduce items to a scalar value. |
| `groupBy` | `groupBy(callable|string $key): static` | Group items by key or resolver callback. |
| `chunk` | `chunk(int $size): static` | Split items into chunks. |
| `flatten` | `flatten(int $depth = 1): static` | Flatten nested arrays by depth. |
| `sort` | `sort(?callable $fn = null): static` | Sort values naturally or with comparator. |
| `sortBy` | `sortBy(string $key, string $direction = 'asc'): static` | Sort associative arrays by field. |
| `unique` | `unique(?callable $fn = null): static` | Remove duplicates by value or resolver. |
| `take` | `take(int $n): static` | Take first N items. |
| `skip` | `skip(int $n): static` | Skip first N items. |
| `tap` | `tap(callable $fn): static` | Run side effects on each item without mutation. |
| `pipe` | `pipe(callable $fn): static` | Replace internal data with callback result array. |
| `through` | `through(string|object $class): static` | Pipe through invokable class or object. |
| `zip` | `zip(array ...$arrays): static` | Zip internal data with other arrays. |
| `merge` | `merge(array ...$arrays): static` | Merge arrays into current items. |
| `keyBy` | `keyBy(callable|string $key): static` | Re-key array by key or callback. |
| `pluck` | `pluck(string $key): static` | Extract key values from arrays. |
| `each` | `each(callable $fn): static` | Iterate with side effects including index. |
| `when` | `when(bool $condition, callable $then, ?callable $otherwise = null): static` | Conditionally apply callbacks. |
| `unless` | `unless(bool $condition, callable $fn): static` | Apply callback when condition is false. |
| `compact` | `compact(): static` | Remove `null`, `false`, empty string, and empty arrays. |
| `flip` | `flip(): static` | Flip keys and values. |
| `toArray` | `toArray(): array` | Export raw array. |
| `toJson` | `toJson(int $flags = 0): string` | Export as JSON string. |
| `first` | `first(?callable $fn = null): mixed` | Get first item or first match. |
| `last` | `last(?callable $fn = null): mixed` | Get last item or last match. |
| `count` | `count(): int` | Count items. |
| `sum` | `sum(callable|string|null $key = null): int\|float` | Sum numeric values or resolved fields. |
| `avg` | `avg(callable|string|null $key = null): float` | Average numeric values or resolved fields. |
| `min` | `min(callable|string|null $key = null): mixed` | Minimum value of collection or resolved field. |
| `max` | `max(callable|string|null $key = null): mixed` | Maximum value of collection or resolved field. |
| `contains` | `contains(mixed $value): bool` and `contains(callable $value): bool` | Check for a value or predicate match. |
| `every` | `every(callable $fn): bool` | Ensure all items match callback. |
| `some` | `some(callable $fn): bool` | Ensure at least one item matches callback. |
| `isEmpty` | `isEmpty(): bool` | Check if pipeline is empty. |
| `isNotEmpty` | `isNotEmpty(): bool` | Check if pipeline has items. |
| `dump` | `dump(): static` | Dump values and continue chaining. |
| `dd` | `dd(): never` | Dump values and terminate execution. |
| `getIterator` | `getIterator(): ArrayIterator` | Iterate with `foreach`. |
| `jsonSerialize` | `jsonSerialize(): mixed` | JSON serialization payload. |

## Why Not Laravel Collection?

- Zero runtime dependencies and no framework requirements.
- Works in any PHP project: CLI tools, legacy systems, libraries, microservices.
- Small surface area focused on array processing without container, macros, or framework integration.
- Predictable behavior with explicit methods and strict typing in PHP 8.1+.

## Contributing

Contributions are welcome. Please open an issue or submit a pull request with tests and clear rationale for changes.
