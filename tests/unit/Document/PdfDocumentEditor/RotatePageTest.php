<?php

declare(strict_types=1);

namespace PhpPdf\Document\PdfDocumentEditor;

use InvalidArgumentException;
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
#[CoversMethod(PdfDocumentEditor::class, 'rotatePage')]
#[UsesClass(PdfDocument::class)]
#[UsesClass(PdfArray::class)]
#[UsesClass(PdfDictionary::class)]
#[UsesClass(PdfIndirectObject::class)]
#[UsesClass(PdfIndirectReference::class)]
#[UsesClass(PdfInteger::class)]
#[UsesClass(PdfName::class)]
#[UsesClass(PdfObjectRegistry::class)]
#[UsesClass(PdfReal::class)]
final class RotatePageTest extends TestCase
{
    use MinimalDocument;

    #[Test]
    public function rotatePageReturnsSelf(): void
    {
        // Arrange
        $editor = new PdfDocumentEditor(self::buildDocument(pageCount: 1));

        // Act
        $result = $editor->rotatePage(0, 90);

        // Assert
        self::assertSame($editor, $result);
    }

    #[Test]
    public function rotatePageAcceptsAllValidDegrees(): void
    {
        // Arrange
        $editor = new PdfDocumentEditor(self::buildDocument(pageCount: 1));

        // Act / Assert — no exception for valid values
        $editor->rotatePage(0, 0);
        $editor->rotatePage(0, 90);
        $editor->rotatePage(0, 180);
        $result = $editor->rotatePage(0, 270);
        self::assertSame($editor, $result);
    }

    #[Test]
    public function rotatePageThrowsForInvalidDegrees(): void
    {
        // Arrange
        $editor = new PdfDocumentEditor(self::buildDocument(pageCount: 1));

        // Act / Assert
        $this->expectException(InvalidArgumentException::class);
        $editor->rotatePage(0, 45);
    }

    #[Test]
    public function rotatePageThrowsForOutOfBoundsIndex(): void
    {
        // Arrange
        $editor = new PdfDocumentEditor(self::buildDocument(pageCount: 1));

        // Act / Assert
        $this->expectException(OutOfBoundsException::class);
        $editor->rotatePage(5, 90);
    }
}
