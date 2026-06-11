<?php

declare(strict_types=1);

namespace PhpPdf\Reader\PdfReadDocument;

use PhpPdf\Object\PdfArray;
use PhpPdf\Object\PdfDictionary;
use PhpPdf\Object\PdfIndirectReference;
use PhpPdf\Object\PdfInteger;
use PhpPdf\Object\PdfName;
use PhpPdf\Object\PdfNull;
use PhpPdf\Reader\MinimalPdfFixture;
use PhpPdf\Reader\PdfLexer;
use PhpPdf\Reader\PdfObjectParser;
use PhpPdf\Reader\PdfReadDocument;
use PhpPdf\Reader\PdfToken;
use PhpPdf\Reader\PdfXRefTable;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(PdfReadDocument::class)]
#[CoversMethod(PdfReadDocument::class, 'getObject')]
#[UsesClass(PdfArray::class)]
#[UsesClass(PdfDictionary::class)]
#[UsesClass(PdfIndirectReference::class)]
#[UsesClass(PdfInteger::class)]
#[UsesClass(PdfName::class)]
#[UsesClass(PdfNull::class)]
#[UsesClass(PdfLexer::class)]
#[UsesClass(PdfObjectParser::class)]
#[UsesClass(PdfToken::class)]
#[UsesClass(PdfXRefTable::class)]
final class GetObjectTest extends TestCase
{
    use MinimalPdfFixture;

    #[Test]
    public function returnsObjectForKnownReference(): void
    {
        // Arrange
        $document = self::createMinimalDocument();

        // Act — object 1 is the Catalog
        $obj = $document->getObject(new PdfIndirectReference(1, 0));

        // Assert
        self::assertInstanceOf(PdfDictionary::class, $obj);
    }

    #[Test]
    public function returnsNullObjectForUnknownReference(): void
    {
        // Arrange
        $document = self::createMinimalDocument();

        // Act — object 999 does not exist
        $obj = $document->getObject(new PdfIndirectReference(999, 0));

        // Assert
        self::assertInstanceOf(PdfNull::class, $obj);
    }

    #[Test]
    public function cachesPreviouslyLoadedObject(): void
    {
        // Arrange
        $document = self::createMinimalDocument();
        $ref = new PdfIndirectReference(1, 0);

        // Act — load twice
        $first = $document->getObject($ref);
        $second = $document->getObject($ref);

        // Assert — same instance returned from cache
        self::assertSame($first, $second);
    }

    #[Test]
    public function resolveObjectPassesThroughNonReference(): void
    {
        // Arrange
        $document = self::createMinimalDocument();
        $dict = new PdfDictionary([]);

        // Act
        $resolved = $document->resolveObject($dict);

        // Assert
        self::assertSame($dict, $resolved);
    }
}
