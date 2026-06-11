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
#[CoversMethod(PdfObjectRegistry::class, 'get')]
#[UsesClass(ObjectRegistryNotFound::class)]
#[UsesClass(PdfIndirectObject::class)]
#[UsesClass(PdfIndirectReference::class)]
#[UsesClass(PdfInteger::class)]
final class GetTest extends TestCase
{
    #[Test]
    public function getReturnsRegisteredObject(): void
    {
        // Arrange
        $registry = new PdfObjectRegistry();
        $obj = new PdfInteger(99);
        $ref = $registry->register($obj);

        // Act / Assert
        self::assertSame($obj, $registry->get($ref));
    }

    #[Test]
    public function getThrowsForUnknownReference(): void
    {
        // Arrange
        $registry = new PdfObjectRegistry();

        // Act / Assert
        $this->expectException(ObjectRegistryNotFound::class);
        $registry->get(new PdfIndirectReference(99, 0));
    }
}
