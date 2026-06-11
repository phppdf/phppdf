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
#[CoversMethod(PdfDocumentEditor::class, 'movePage')]
#[UsesClass(PdfDocument::class)]
#[UsesClass(PdfArray::class)]
#[UsesClass(PdfDictionary::class)]
#[UsesClass(PdfIndirectObject::class)]
#[UsesClass(PdfIndirectReference::class)]
#[UsesClass(PdfInteger::class)]
#[UsesClass(PdfName::class)]
#[UsesClass(PdfObjectRegistry::class)]
#[UsesClass(PdfReal::class)]
final class MovePageTest extends TestCase
{
    use MinimalDocument;

    #[Test]
    public function movePageReturnsSelf(): void
    {
        // Arrange
        $editor = new PdfDocumentEditor(self::buildDocument(pageCount: 2));

        // Act
        $result = $editor->movePage(0, 1);

        // Assert
        self::assertSame($editor, $result);
    }

    #[Test]
    public function movePageDoesNotChangePageCount(): void
    {
        // Arrange
        $editor = new PdfDocumentEditor(self::buildDocument(pageCount: 3));

        // Act
        $editor->movePage(2, 0);

        // Assert
        self::assertSame(3, $editor->getPageCount());
    }

    #[Test]
    public function movePageThrowsForInvalidFromIndex(): void
    {
        // Arrange
        $editor = new PdfDocumentEditor(self::buildDocument(pageCount: 2));

        // Act / Assert
        $this->expectException(OutOfBoundsException::class);
        $editor->movePage(5, 0);
    }

    #[Test]
    public function movePageThrowsForInvalidToIndex(): void
    {
        // Arrange
        $editor = new PdfDocumentEditor(self::buildDocument(pageCount: 2));

        // Act / Assert
        $this->expectException(OutOfBoundsException::class);
        $editor->movePage(0, 5);
    }
}
