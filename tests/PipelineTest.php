<?php

declare(strict_types=1);

namespace ArrayPipeline\Tests;

use ArrayPipeline\Pipeline;
use PHPUnit\Framework\TestCase;

final class PipelineTest extends TestCase
{
    /**
     * @return void
     */
    public function testFromAndConstructorCreateEquivalentPipelines(): void
    {
        $from = Pipeline::from([1, 2, 3]);
        $new = new Pipeline([1, 2, 3]);

        self::assertSame([1, 2, 3], $from->toArray());
        self::assertSame($new->toArray(), $from->toArray());
    }

    /**
     * @return void
     */
    public function testBasicChainingFilterMapToArray(): void
    {
        $result = Pipeline::from([1, 2, 3, 4, 5])
            ->filter(fn (int $value): bool => $value % 2 === 1)
            ->map(fn (int $value): int => $value * 10)
            ->toArray();

        self::assertSame([10, 30, 50], $result);
        self::assertCount(3, $result);
    }

    /**
     * @return void
     */
    public function testRejectRemovesMatchingItems(): void
    {
        $result = Pipeline::from([1, 2, 3, 4])
            ->reject(fn (int $value): bool => $value <= 2)
            ->toArray();

        self::assertSame([3, 4], $result);
        self::assertNotContains(2, $result);
    }

    /**
     * @return void
     */
    public function testGroupByWithStringAndCallable(): void
    {
        $items = [
            ['type' => 'fruit', 'name' => 'apple'],
            ['type' => 'fruit', 'name' => 'pear'],
            ['type' => 'veg', 'name' => 'carrot'],
        ];

        $groupByType = Pipeline::from($items)->groupBy('type')->toArray();
        $groupByLength = Pipeline::from($items)->groupBy(
            fn (array $item): int => strlen($item['name'])
        )->toArray();

        self::assertCount(2, $groupByType);
        self::assertCount(3, $groupByLength);
    }

    /**
     * @return void
     */
    public function testChunkProducesExpectedArrays(): void
    {
        $chunks = Pipeline::from([1, 2, 3, 4, 5])->chunk(2)->toArray();

        self::assertSame([[1, 2], [3, 4], [5]], $chunks);
        self::assertCount(3, $chunks);
    }

    /**
     * @return void
     */
    public function testFlattenSupportsDepthOneAndTwo(): void
    {
        $source = [1, [2, [3, 4]], 5];

        $depthOne = Pipeline::from($source)->flatten(1)->toArray();
        $depthTwo = Pipeline::from($source)->flatten(2)->toArray();

        self::assertSame([1, 2, [3, 4], 5], $depthOne);
        self::assertSame([1, 2, 3, 4, 5], $depthTwo);
    }

    /**
     * @return void
     */
    public function testSortWithAndWithoutComparator(): void
    {
        $default = Pipeline::from([3, 1, 2])->sort()->toArray();
        $custom = Pipeline::from([['v' => 1], ['v' => 3], ['v' => 2]])
            ->sort(fn (array $a, array $b): int => $b['v'] <=> $a['v'])
            ->toArray();

        self::assertSame([1, 2, 3], $default);
        self::assertSame([3, 2, 1], array_column($custom, 'v'));
    }

    /**
     * @return void
     */
    public function testSortByAscendingAndDescending(): void
    {
        $items = [
            ['score' => 10],
            ['score' => 30],
            ['score' => 20],
        ];

        $asc = Pipeline::from($items)->sortBy('score', 'asc')->toArray();
        $desc = Pipeline::from($items)->sortBy('score', 'desc')->toArray();

        self::assertSame([10, 20, 30], array_column($asc, 'score'));
        self::assertSame([30, 20, 10], array_column($desc, 'score'));
    }

    /**
     * @return void
     */
    public function testUniqueTakeAndSkipWorkTogether(): void
    {
        $unique = Pipeline::from([1, 1, 2, 3, 3])->unique()->toArray();
        $window = Pipeline::from([10, 20, 30, 40])->skip(1)->take(2)->toArray();

        self::assertSame([1, 2, 3], $unique);
        self::assertSame([20, 30], $window);
    }

    /**
     * @return void
     */
    public function testTapAndEachRunSideEffectsWithoutMutation(): void
    {
        $tapLog = [];
        $eachLog = [];
        $pipeline = Pipeline::from([5, 6, 7]);

        $pipeline->tap(function (int $value) use (&$tapLog): void {
            $tapLog[] = $value;
        })->each(function (int $value, int $index) use (&$eachLog): void {
            $eachLog[] = $index . ':' . $value;
        });

        self::assertSame([5, 6, 7], $pipeline->toArray());
        self::assertSame(['0:5', '1:6', '2:7'], $eachLog);
        self::assertSame([5, 6, 7], $tapLog);
    }

    /**
     * @return void
     */
    public function testPipeAndThroughReplaceData(): void
    {
        $piped = Pipeline::from([1, 2, 3])->pipe(fn (array $items): array => array_reverse($items))->toArray();
        $through = Pipeline::from([1, 2, 3])->through(DoubleValuesPipe::class)->toArray();

        self::assertSame([3, 2, 1], $piped);
        self::assertSame([2, 4, 6], $through);
    }

    /**
     * @return void
     */
    public function testZipWithTwoAndThreeArrays(): void
    {
        $zippedTwo = Pipeline::from([1, 2])->zip(['a', 'b'])->toArray();
        $zippedThree = Pipeline::from([1, 2])->zip(['a', 'b'], ['x', 'y'])->toArray();

        self::assertSame([[1, 'a'], [2, 'b']], $zippedTwo);
        self::assertSame([[1, 'a', 'x'], [2, 'b', 'y']], $zippedThree);
    }

    /**
     * @return void
     */
    public function testMergeCombinesMultipleArrays(): void
    {
        $result = Pipeline::from([1, 2])->merge([3], [4, 5])->toArray();

        self::assertSame([1, 2, 3, 4, 5], $result);
        self::assertCount(5, $result);
    }

    /**
     * @return void
     */
    public function testKeyByAndPluck(): void
    {
        $items = [
            ['id' => 10, 'name' => 'A'],
            ['id' => 20, 'name' => 'B'],
        ];

        $keyed = Pipeline::from($items)->keyBy('id')->toArray();
        $names = Pipeline::from($items)->pluck('name')->toArray();

        self::assertArrayHasKey(10, $keyed);
        self::assertSame(['A', 'B'], $names);
    }

    /**
     * @return void
     */
    public function testWhenAndUnlessConditionallyApplyCallbacks(): void
    {
        $whenTrue = Pipeline::from([1, 2, 3])->when(true, fn (Pipeline $p): Pipeline => $p->map(fn (int $v): int => $v * 2));
        $whenFalse = Pipeline::from([1, 2, 3])->when(
            false,
            fn (Pipeline $p): Pipeline => $p->map(fn (int $v): int => $v * 2),
            fn (Pipeline $p): Pipeline => $p->map(fn (int $v): int => $v + 1)
        );
        $unless = Pipeline::from([1, 2, 3])->unless(false, fn (Pipeline $p): Pipeline => $p->skip(1));

        self::assertSame([2, 4, 6], $whenTrue->toArray());
        self::assertSame([2, 3, 4], $whenFalse->toArray());
        self::assertSame([2, 3], $unless->toArray());
    }

    /**
     * @return void
     */
    public function testCompactAndFlip(): void
    {
        $compacted = Pipeline::from([null, false, '', [], 0, '0', 'ok'])->compact()->toArray();
        $flipped = Pipeline::from(['a', 'b'])->flip()->toArray();

        self::assertSame([0, '0', 'ok'], $compacted);
        self::assertSame(['a' => 0, 'b' => 1], $flipped);
    }

    /**
     * @return void
     */
    public function testReduceReturnsScalar(): void
    {
        $sum = Pipeline::from([1, 2, 3])->reduce(fn (int $carry, int $item): int => $carry + $item, 0);
        $concat = Pipeline::from(['a', 'b'])->reduce(fn (string $carry, string $item): string => $carry . $item, '');

        self::assertSame(6, $sum);
        self::assertSame('ab', $concat);
    }

    /**
     * @return void
     */
    public function testTerminalMathMethodsWithAndWithoutKey(): void
    {
        $flat = Pipeline::from([1, 2, 3, 4]);
        $assoc = Pipeline::from([['n' => 2], ['n' => 4], ['n' => 6]]);

        self::assertSame(10, $flat->sum());
        self::assertSame(12, $assoc->sum('n'));
        self::assertSame(2.5, $flat->avg());
        self::assertSame(4.0, $assoc->avg('n'));
        self::assertSame(1, $flat->min());
        self::assertSame(6, $assoc->max('n'));
    }

    /**
     * @return void
     */
    public function testFirstLastContainsEverySomeAndStateChecks(): void
    {
        $pipeline = Pipeline::from([1, 2, 3, 4]);

        self::assertSame(1, $pipeline->first());
        self::assertSame(4, $pipeline->last());
        self::assertSame(3, $pipeline->first(fn (int $v): bool => $v > 2));
        self::assertSame(2, $pipeline->last(fn (int $v): bool => $v < 3));
        self::assertTrue($pipeline->contains(2));
        self::assertTrue($pipeline->contains(fn (int $v): bool => $v === 4));
        self::assertTrue($pipeline->every(fn (int $v): bool => $v > 0));
        self::assertTrue($pipeline->some(fn (int $v): bool => $v > 3));
        self::assertFalse($pipeline->isEmpty());
        self::assertTrue($pipeline->isNotEmpty());
    }

    /**
     * @return void
     */
    public function testCountableIteratorJsonSerializableAndToJson(): void
    {
        $pipeline = Pipeline::from([['id' => 1], ['id' => 2]]);
        $seen = [];

        foreach ($pipeline as $item) {
            $seen[] = $item['id'];
        }

        $encoded = json_encode($pipeline, JSON_THROW_ON_ERROR);
        $json = $pipeline->toJson(JSON_THROW_ON_ERROR);

        self::assertSame(2, count($pipeline));
        self::assertSame([1, 2], $seen);
        self::assertSame('[{"id":1},{"id":2}]', $encoded);
        self::assertSame('[{"id":1},{"id":2}]', $json);
    }

    /**
     * @return void
     */
    public function testDumpReturnsSameInstanceAndProducesOutput(): void
    {
        $pipeline = Pipeline::from([1, 2]);

        ob_start();
        $returned = $pipeline->dump();
        $output = (string) ob_get_clean();

        self::assertSame($pipeline, $returned);
        self::assertStringContainsString('array(2)', $output);
    }
}

final class DoubleValuesPipe
{
    /**
     * @param array<int> $items
     * @return array<int>
     */
    public function __invoke(array $items): array
    {
        return array_map(fn (int $item): int => $item * 2, $items);
    }
}
