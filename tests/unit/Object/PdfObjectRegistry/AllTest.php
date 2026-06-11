<?php

declare(strict_types=1);

namespace PhpPdf\Object\PdfObjectRegistry;

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
#[CoversMethod(PdfObjectRegistry::class, 'all')]
#[UsesClass(PdfIndirectObject::class)]
#[UsesClass(PdfIndirectReference::class)]
#[UsesClass(PdfInteger::class)]
final class AllTest extends TestCase
{
    #[Test]
    public function allReturnsEmptyWhenNoObjectsRegistered(): void
    {
        self::assertSame([], (new PdfObjectRegistry())->all());
    }

    #[Test]
    public function allReturnsAllRegisteredObjects(): void
    {
        // Arrange
        $registry = new PdfObjectRegistry();
        $registry->register(new PdfInteger(1));
        $registry->register(new PdfInteger(2));

        // Act
        $all = $registry->all();

        // Assert
        self::assertCount(2, $all);
    }
}
