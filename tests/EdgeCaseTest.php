<?php

declare(strict_types=1);

namespace ArrayPipeline\Tests;

use ArrayPipeline\Exceptions\PipelineException;
use ArrayPipeline\Pipeline;
use PHPUnit\Framework\TestCase;

final class EdgeCaseTest extends TestCase
{
    /**
     * @return void
     */
    public function testEmptyArrayTerminalMethodsReturnSensibleDefaults(): void
    {
        $pipeline = Pipeline::from([]);

        self::assertSame([], $pipeline->toArray());
        self::assertSame('[]', $pipeline->toJson());
        self::assertNull($pipeline->first());
        self::assertNull($pipeline->last());
        self::assertSame(0, $pipeline->count());
        self::assertSame(0, $pipeline->sum());
        self::assertSame(0.0, $pipeline->avg());
        self::assertNull($pipeline->min());
        self::assertNull($pipeline->max());
        self::assertFalse($pipeline->contains(1));
        self::assertTrue($pipeline->every(fn (): bool => false));
        self::assertFalse($pipeline->some(fn (): bool => true));
        self::assertTrue($pipeline->isEmpty());
        self::assertFalse($pipeline->isNotEmpty());
    }

    /**
     * @return void
     */
    public function testReduceOnEmptyUsesInitialValue(): void
    {
        $result = Pipeline::from([])->reduce(fn (int $carry, int $item): int => $carry + $item, 100);

        self::assertSame(100, $result);
        self::assertIsInt($result);
    }

    /**
     * @return void
     */
    public function testChunkOneAndChunkArrayLength(): void
    {
        $source = [1, 2, 3];

        $chunkOne = Pipeline::from($source)->chunk(1)->toArray();
        $chunkThree = Pipeline::from($source)->chunk(3)->toArray();

        self::assertSame([[1], [2], [3]], $chunkOne);
        self::assertSame([[1, 2, 3]], $chunkThree);
    }

    /**
     * @return void
     */
    public function testFlattenAlreadyFlatArrayRemainsSame(): void
    {
        $result = Pipeline::from([1, 2, 3])->flatten()->toArray();

        self::assertSame([1, 2, 3], $result);
        self::assertCount(3, $result);
    }

    /**
     * @return void
     */
    public function testUniqueWithDuplicateObjectsByCallable(): void
    {
        $a = (object) ['id' => 1, 'name' => 'A'];
        $b = (object) ['id' => 1, 'name' => 'B'];
        $c = (object) ['id' => 2, 'name' => 'C'];

        $result = Pipeline::from([$a, $b, $c])
            ->unique(fn (object $item): int => $item->id)
            ->toArray();

        self::assertCount(2, $result);
        self::assertSame([1, 2], array_map(fn (object $item): int => $item->id, $result));
    }

    /**
     * @return void
     */
    public function testThroughWithNonInvokableClassThrowsException(): void
    {
        $this->expectException(PipelineException::class);
        $this->expectExceptionMessage('Class ArrayPipeline\Tests\NonInvokablePipe does not implement __invoke');

        Pipeline::from([1, 2])->through(NonInvokablePipe::class);
    }

    /**
     * @return void
     */
    public function testChunkWithZeroThrowsException(): void
    {
        $this->expectException(PipelineException::class);
        $this->expectExceptionMessage('Chunk size must be greater than 0, got 0');

        Pipeline::from([1, 2])->chunk(0);
    }

    /**
     * @return void
     */
    public function testGroupByMissingKeyThrowsException(): void
    {
        $this->expectException(PipelineException::class);
        $this->expectExceptionMessage("Key 'missing' not found in pipeline item");

        Pipeline::from([['id' => 1]])->groupBy('missing');
    }

    /**
     * @return void
     */
    public function testSortByMissingKeyThrowsException(): void
    {
        $this->expectException(PipelineException::class);
        $this->expectExceptionMessage("Key 'missing' not found in pipeline item");

        Pipeline::from([['id' => 1]])->sortBy('missing');
    }

    /**
     * @return void
     */
    public function testVeryLargeArrayChainDoesNotCrash(): void
    {
        $input = range(1, 10000);

        $result = Pipeline::from($input)
            ->skip(100)
            ->take(5000)
            ->filter(fn (int $v): bool => $v % 2 === 0)
            ->map(fn (int $v): int => $v * 3)
            ->sum();

        self::assertGreaterThan(0, $result);
        self::assertIsInt($result);
    }
}

final class NonInvokablePipe
{
}
