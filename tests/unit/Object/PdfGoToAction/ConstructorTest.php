<?php

declare(strict_types=1);

namespace PhpPdf\Object\PdfGoToAction;

use PhpPdf\Object\PdfArray;
use PhpPdf\Object\PdfDestination;
use PhpPdf\Object\PdfDictionary;
use PhpPdf\Object\PdfGoToAction;
use PhpPdf\Object\PdfIndirectReference;
use PhpPdf\Object\PdfName;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(PdfGoToAction::class)]
#[CoversMethod(PdfGoToAction::class, '__construct')]
#[UsesClass(PdfArray::class)]
#[UsesClass(PdfDestination::class)]
#[UsesClass(PdfDictionary::class)]
#[UsesClass(PdfIndirectReference::class)]
#[UsesClass(PdfName::class)]
final class ConstructorTest extends TestCase
{
    #[Test]
    public function constructorCreatesInstance(): void
    {
        // Arrange
        $dest = new PdfDestination(new PdfIndirectReference(1, 0));

        // Act
        $obj = new PdfGoToAction($dest);

        // Assert
        self::assertInstanceOf(PdfDictionary::class, $obj);
    }
}
