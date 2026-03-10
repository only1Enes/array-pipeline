<?php

declare(strict_types=1);

namespace ArrayPipeline\Exceptions;

use RuntimeException;

class PipelineException extends RuntimeException
{
    /**
     * @param string $class
     * @return self
     */
    public static function notInvokable(string $class): self
    {
        return new self(sprintf('Class %s does not implement __invoke', $class));
    }

    /**
     * @param int $size
     * @return self
     */
    public static function invalidChunkSize(int $size): self
    {
        return new self(sprintf('Chunk size must be greater than 0, got %d', $size));
    }

    /**
     * @param string $key
     * @return self
     */
    public static function keyNotFound(string $key): self
    {
        return new self(sprintf("Key '%s' not found in pipeline item", $key));
    }
}
