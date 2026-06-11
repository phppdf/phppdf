<?php

declare(strict_types=1);

namespace PhpPdf\Document\PdfTrailer;

use PhpPdf\Document\PdfTrailer;
use PhpPdf\Object\PdfDictionary;
use PhpPdf\Object\PdfName;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(PdfTrailer::class)]
#[UsesClass(PdfDictionary::class)]
#[UsesClass(PdfName::class)]
final class ConstructorTest extends TestCase
{
    #[Test]
    public function trailerExtendssPdfDictionary(): void
    {
        // Arrange / Act
        $trailer = new PdfTrailer([]);

        // Assert
        self::assertInstanceOf(PdfDictionary::class, $trailer);
    }

    #[Test]
    public function trailerStoresEntries(): void
    {
        // Arrange
        $name = new PdfName('Catalog');

        // Act
        $trailer = new PdfTrailer(['Root' => $name]);

        // Assert
        self::assertSame($name, $trailer->get('Root'));
    }
}
