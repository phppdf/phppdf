<?php

declare(strict_types=1);

namespace PhpPdf\Document\PdfDocumentEditor;

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
#[CoversMethod(PdfDocumentEditor::class, 'footer')]
#[UsesClass(PdfDocument::class)]
#[UsesClass(PdfArray::class)]
#[UsesClass(PdfDictionary::class)]
#[UsesClass(PdfIndirectObject::class)]
#[UsesClass(PdfIndirectReference::class)]
#[UsesClass(PdfInteger::class)]
#[UsesClass(PdfName::class)]
#[UsesClass(PdfObjectRegistry::class)]
#[UsesClass(PdfReal::class)]
final class FooterTest extends TestCase
{
    use MinimalDocument;

    #[Test]
    public function footerReturnsSelf(): void
    {
        // Arrange
        $editor = new PdfDocumentEditor(self::buildDocument());

        // Act
        $result = $editor->footer(static fn () => null);

        // Assert
        self::assertSame($editor, $result);
    }
}
