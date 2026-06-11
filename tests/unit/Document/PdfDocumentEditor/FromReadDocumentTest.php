<?php

declare(strict_types=1);

namespace PhpPdf\Document\PdfDocumentEditor;

use PhpPdf\Document\PdfDocument;
use PhpPdf\Document\PdfDocumentEditor;
use PhpPdf\Document\PdfObjectImporter;
use PhpPdf\Object\PdfArray;
use PhpPdf\Object\PdfDictionary;
use PhpPdf\Object\PdfIndirectObject;
use PhpPdf\Object\PdfIndirectReference;
use PhpPdf\Object\PdfInteger;
use PhpPdf\Object\PdfName;
use PhpPdf\Object\PdfObjectRegistry;
use PhpPdf\Object\PdfReal;
use PhpPdf\Object\PdfString;
use PhpPdf\Object\PdfVersion;
use PhpPdf\Reader\PdfLexer;
use PhpPdf\Reader\PdfObjectParser;
use PhpPdf\Reader\PdfReadDocument;
use PhpPdf\Reader\PdfToken;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use RuntimeException;

#[CoversClass(PdfDocumentEditor::class)]
#[CoversMethod(PdfDocumentEditor::class, 'fromReadDocument')]
#[UsesClass(PdfDocument::class)]
#[UsesClass(PdfArray::class)]
#[UsesClass(PdfDictionary::class)]
#[UsesClass(PdfIndirectObject::class)]
#[UsesClass(PdfIndirectReference::class)]
#[UsesClass(PdfInteger::class)]
#[UsesClass(PdfLexer::class)]
#[UsesClass(PdfName::class)]
#[UsesClass(PdfObjectImporter::class)]
#[UsesClass(PdfObjectRegistry::class)]
#[UsesClass(PdfReal::class)]
#[UsesClass(PdfObjectParser::class)]
#[UsesClass(PdfReadDocument::class)]
#[UsesClass(PdfString::class)]
#[UsesClass(PdfToken::class)]
final class FromReadDocumentTest extends TestCase
{
    #[Test]
    public function fromReadDocumentThrowsWhenTrailerHasNoRoot(): void
    {
        // Arrange — trailer has no /Root entry
        $source = new PdfReadDocument(
            PdfLexer::fromString(''),
            [],
            new PdfDictionary([]),
            PdfVersion::PDF_1_7,
        );

        // Act / Assert
        $this->expectException(RuntimeException::class);
        PdfDocumentEditor::fromReadDocument($source);
    }

    #[Test]
    public function fromReadDocumentReturnEditorWithImportedPages(): void
    {
        // Arrange — minimal PDF string with catalog, pages tree, and one page
        $source = $this->buildMinimalReadDocument();

        // Act
        $editor = PdfDocumentEditor::fromReadDocument($source);

        // Assert
        self::assertInstanceOf(PdfDocumentEditor::class, $editor);
        self::assertSame(1, $editor->getPageCount());
    }

    #[Test]
    public function fromReadDocumentImportsInfoDictWhenPresent(): void
    {
        // Arrange — trailer has both /Root and /Info
        $source = $this->buildMinimalReadDocument(withInfo: true);

        // Act
        $editor = PdfDocumentEditor::fromReadDocument($source);

        // Assert
        self::assertInstanceOf(PdfDocumentEditor::class, $editor);
    }

    // -------------------------------------------------------------------------

    /**
     * Builds a PdfReadDocument backed by a minimal in-memory PDF string with
     * properly calculated byte offsets so the lexer can resolve indirect objects.
     */
    private function buildMinimalReadDocument(bool $withInfo = false): PdfReadDocument
    {
        $content = '';
        $xref = [];

        $objects = [
            1 => '<< /Type /Catalog /Pages 2 0 R >>',
            2 => '<< /Type /Pages /Kids [3 0 R] /Count 1 >>',
            3 => '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 595 842] >>',
        ];

        if ($withInfo) {
            $objects[4] = '<< /Title (Test) >>';
        }

        ksort($objects);

        foreach ($objects as $num => $raw) {
            $xref[$num] = ['offset' => strlen($content), 'generation' => 0, 'type' => 'n'];
            $content .= "{$num} 0 obj\n{$raw}\nendobj\n";
        }

        $trailerEntries = ['Root' => new PdfIndirectReference(1, 0)];

        if ($withInfo) {
            $trailerEntries['Info'] = new PdfIndirectReference(4, 0);
        }

        return new PdfReadDocument(
            PdfLexer::fromString($content),
            $xref,
            new PdfDictionary($trailerEntries),
            PdfVersion::PDF_1_7,
        );
    }
}
