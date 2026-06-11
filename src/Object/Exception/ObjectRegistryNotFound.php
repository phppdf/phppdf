<?php

declare(strict_types=1);

namespace PhpPdf\Object\Exception;

use InvalidArgumentException;
use PhpPdf\Object\PdfIndirectReference;

/**
 * Exception thrown when an indirect object cannot be found in the registry.
 *
 * Provides named factory methods that produce descriptive messages including
 * the object number and, where applicable, the generation number.
 */
final class ObjectRegistryNotFound extends InvalidArgumentException
{
    /**
     * Creates an exception for a lookup by object number with no generation context.
     */
    public static function forObjectNumber(int $objectNumber): self
    {
        $msg = sprintf('The object with object number %d is not found.', $objectNumber);

        return new self($msg);
    }

    /**
     * Creates an exception for a lookup where both object number and generation number are known.
     */
    public static function forGenerationNumber(int $objectNumber, int $generationNumber): self
    {
        $msg = sprintf(
            'The object with object number %d and generation number %d is not found.',
            $objectNumber,
            $generationNumber,
        );

        return new self($msg);
    }

    /**
     * Creates an exception from a PdfIndirectReference that could not be resolved.
     */
    public static function forReference(PdfIndirectReference $reference): self
    {
        $msg = sprintf(
            'The object with object number %d and generation number %d is not found.',
            $reference->getObjectNumber(),
            $reference->getGenerationNumber(),
        );

        return new self($msg);
    }
}
