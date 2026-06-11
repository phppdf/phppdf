<?php

declare(strict_types=1);

namespace PhpPdf\Object\PdfDestination;

use PhpPdf\Object\PdfArray;
use PhpPdf\Object\PdfDestination;
use PhpPdf\Object\PdfIndirectReference;
use PhpPdf\Object\PdfName;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(PdfDestination::class)]
#[CoversMethod(PdfDestination::class, '__construct')]
#[UsesClass(PdfArray::class)]
#[UsesClass(PdfIndirectReference::class)]
#[UsesClass(PdfName::class)]
final class ConstructorTest extends TestCase
{
    #[Test]
    public function constructorCreatesInstance(): void
    {
        // Arrange / Act
        $obj = new PdfDestination(new PdfIndirectReference(1, 0));

        // Assert
        self::assertInstanceOf(PdfArray::class, $obj);
    }
}
