<?php

declare(strict_types=1);

namespace PhpPdf\Object\PdfLinkAnnotation;

use PhpPdf\Object\PdfArray;
use PhpPdf\Object\PdfDictionary;
use PhpPdf\Object\PdfInteger;
use PhpPdf\Object\PdfLinkAnnotation;
use PhpPdf\Object\PdfName;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(PdfLinkAnnotation::class)]
#[CoversMethod(PdfLinkAnnotation::class, '__construct')]
#[UsesClass(PdfArray::class)]
#[UsesClass(PdfDictionary::class)]
#[UsesClass(PdfInteger::class)]
#[UsesClass(PdfName::class)]
final class ConstructorTest extends TestCase
{
    #[Test]
    public function constructorCreatesInstance(): void
    {
        // Arrange
        $rect = new PdfArray([]);
        $action = new PdfDictionary([]);

        // Act
        $obj = new PdfLinkAnnotation($rect, $action);

        // Assert
        self::assertInstanceOf(PdfDictionary::class, $obj);
    }
}
