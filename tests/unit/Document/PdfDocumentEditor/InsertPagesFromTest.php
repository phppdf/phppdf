<?php

declare(strict_types=1);

namespace PhpPdf\Document\PdfDocumentEditor;

use OutOfBoundsException;
use PhpPdf\Document\PdfDocument;
use PhpPdf\Document\PdfDocumentEditor;
use PhpPdf\Object\PdfArray;
use PhpPdf\Object\PdfDictionary;
use PhpPdf\Object\PdfIndirectObject;
use PhpPdf\Object\PdfIndirectReference;
use PhpPdf\Object\PdfInteger;
use PhpPdf\Object\PdfName;
use PhpPdf\Object\PdfObjectRegistry;
use PhpPdf\Object\PdfReal;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(PdfDocumentEditor::class)]
#[CoversMethod(PdfDocumentEditor::class, 'insertPagesFrom')]
#[UsesClass(PdfDocument::class)]
#[UsesClass(PdfArray::class)]
#[UsesClass(PdfDictionary::class)]
#[UsesClass(PdfIndirectObject::class)]
#[UsesClass(PdfIndirectReference::class)]
#[UsesClass(PdfInteger::class)]
#[UsesClass(PdfName::class)]
#[UsesClass(PdfObjectRegistry::class)]
#[UsesClass(PdfReal::class)]
final class InsertPagesFromTest extends TestCase
{
    use MinimalDocument;

    #[Test]
    public function insertPagesFromReturnsSelf(): void
    {
        // Arrange
        $editor = new PdfDocumentEditor(self::buildDocument(pageCount: 1));
        $extra = self::buildDocument(pageCount: 1);

        // Act
        $result = $editor->insertPagesFrom($extra, before: 0);

        // Assert
        self::assertSame($editor, $result);
    }

    #[Test]
    public function insertPagesFromIncreasesPageCount(): void
    {
        // Arrange
        $editor = new PdfDocumentEditor(self::buildDocument(pageCount: 1));
        $extra = self::buildDocument(pageCount: 2);

        // Act
        $editor->insertPagesFrom($extra, before: 0);

        // Assert
        self::assertSame(3, $editor->getPageCount());
    }

    #[Test]
    public function insertPagesFromAppendAtEndWhenBeforeEqualsPageCount(): void
    {
        // Arrange
        $editor = new PdfDocumentEditor(self::buildDocument(pageCount: 1));
        $extra = self::buildDocument(pageCount: 1);

        // Act
        $editor->insertPagesFrom($extra, before: $editor->getPageCount());

        // Assert
        self::assertSame(2, $editor->getPageCount());
    }

    #[Test]
    public function insertPagesFromThrowsForNegativeBeforeIndex(): void
    {
        // Arrange
        $editor = new PdfDocumentEditor(self::buildDocument(pageCount: 1));
        $extra = self::buildDocument(pageCount: 1);

        // Act / Assert
        $this->expectException(OutOfBoundsException::class);
        $editor->insertPagesFrom($extra, before: -1);
    }

    #[Test]
    public function insertPagesFromThrowsWhenBeforeExceedsPageCount(): void
    {
        // Arrange
        $editor = new PdfDocumentEditor(self::buildDocument(pageCount: 1));
        $extra = self::buildDocument(pageCount: 1);

        // Act / Assert
        $this->expectException(OutOfBoundsException::class);
        $editor->insertPagesFrom($extra, before: 5);
    }
}
