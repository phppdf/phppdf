<?php

declare(strict_types=1);

namespace PhpPdf\Object\PdfObjectRegistry;

use PhpPdf\Object\Exception\ObjectRegistryNotFound;
use PhpPdf\Object\PdfIndirectObject;
use PhpPdf\Object\PdfIndirectReference;
use PhpPdf\Object\PdfInteger;
use PhpPdf\Object\PdfObjectRegistry;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(PdfObjectRegistry::class)]
#[CoversMethod(PdfObjectRegistry::class, 'update')]
#[CoversMethod(PdfObjectRegistry::class, 'getLatestGeneration')]
#[UsesClass(ObjectRegistryNotFound::class)]
#[UsesClass(PdfIndirectObject::class)]
#[UsesClass(PdfIndirectReference::class)]
#[UsesClass(PdfInteger::class)]
final class UpdateTest extends TestCase
{
    #[Test]
    public function updateReplacesObjectAndIncrementsGeneration(): void
    {
        // Arrange
        $registry = new PdfObjectRegistry();
        $ref = $registry->register(new PdfInteger(1));

        // Act
        $newRef = $registry->update($ref, new PdfInteger(2));

        // Assert
        self::assertSame(1, $newRef->getGenerationNumber());
        $updated = $registry->get($newRef);
        self::assertInstanceOf(PdfInteger::class, $updated);
        self::assertSame(2, $updated->getValue());
    }

    #[Test]
    public function updateThrowsForNonExistentObject(): void
    {
        // Arrange
        $registry = new PdfObjectRegistry();

        // Act / Assert
        $this->expectException(ObjectRegistryNotFound::class);
        $registry->update(new PdfIndirectReference(99, 0), new PdfInteger(1));
    }
}
