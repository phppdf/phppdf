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
#[CoversMethod(PdfDocumentEditor::class, 'removePage')]
#[UsesClass(PdfDocument::class)]
#[UsesClass(PdfArray::class)]
#[UsesClass(PdfDictionary::class)]
#[UsesClass(PdfIndirectObject::class)]
#[UsesClass(PdfIndirectReference::class)]
#[UsesClass(PdfInteger::class)]
#[UsesClass(PdfName::class)]
#[UsesClass(PdfObjectRegistry::class)]
#[UsesClass(PdfReal::class)]
final class RemovePageTest extends TestCase
{
    use MinimalDocument;

    #[Test]
    public function removePageReturnsSelf(): void
    {
        // Arrange
        $editor = new PdfDocumentEditor(self::buildDocument(pageCount: 2));

        // Act
        $result = $editor->removePage(0);

        // Assert
        self::assertSame($editor, $result);
    }

    #[Test]
    public function removePageDecreasesPageCount(): void
    {
        // Arrange
        $editor = new PdfDocumentEditor(self::buildDocument(pageCount: 2));

        // Act
        $editor->removePage(0);

        // Assert
        self::assertSame(1, $editor->getPageCount());
    }

    #[Test]
    public function removePageThrowsForNegativeIndex(): void
    {
        // Arrange
        $editor = new PdfDocumentEditor(self::buildDocument(pageCount: 1));

        // Act / Assert
        $this->expectException(OutOfBoundsException::class);
        $editor->removePage(-1);
    }

    #[Test]
    public function removePageThrowsForIndexOutOfRange(): void
    {
        // Arrange
        $editor = new PdfDocumentEditor(self::buildDocument(pageCount: 1));

        // Act / Assert
        $this->expectException(OutOfBoundsException::class);
        $editor->removePage(1);
    }
}
