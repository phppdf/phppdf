<?php

declare(strict_types=1);

namespace PhpPdf\Object\PdfResources;

use PhpPdf\Object\PdfDictionary;
use PhpPdf\Object\PdfIndirectReference;
use PhpPdf\Object\PdfResources;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(PdfResources::class)]
#[CoversMethod(PdfResources::class, '__construct')]
#[UsesClass(PdfDictionary::class)]
#[UsesClass(PdfIndirectReference::class)]
final class ConstructorTest extends TestCase
{
    #[Test]
    public function constructorCreatesEmptyResources(): void
    {
        // Arrange / Act
        $obj = new PdfResources();

        // Assert
        self::assertInstanceOf(PdfDictionary::class, $obj);
    }

    #[Test]
    public function constructorCreatesResourcesWithFonts(): void
    {
        // Arrange / Act
        $obj = new PdfResources(['F1' => new PdfIndirectReference(3, 0)]);

        // Assert
        self::assertInstanceOf(PdfDictionary::class, $obj);
    }
}
