<?php

declare(strict_types=1);

namespace PhpPdf\Reader\PdfAcroFormFiller;

use PhpPdf\Object\PdfArray;
use PhpPdf\Object\PdfBoolean;
use PhpPdf\Object\PdfDictionary;
use PhpPdf\Object\PdfIndirectReference;
use PhpPdf\Object\PdfInteger;
use PhpPdf\Object\PdfName;
use PhpPdf\Object\PdfNull;
use PhpPdf\Object\PdfString;
use PhpPdf\Object\PdfVersion;
use PhpPdf\Reader\PdfAcroFormFiller;
use PhpPdf\Reader\PdfAcroFormReader;
use PhpPdf\Reader\PdfFormField;
use PhpPdf\Reader\PdfFormFieldType;
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

#[CoversClass(PdfAcroFormFiller::class)]
#[CoversMethod(PdfAcroFormFiller::class, 'setText')]
#[CoversMethod(PdfAcroFormFiller::class, 'setChecked')]
#[UsesClass(PdfAcroFormReader::class)]
#[UsesClass(PdfArray::class)]
#[UsesClass(PdfBoolean::class)]
#[UsesClass(PdfDictionary::class)]
#[UsesClass(PdfFormField::class)]
#[UsesClass(PdfFormFieldType::class)]
#[UsesClass(PdfIndirectReference::class)]
#[UsesClass(PdfInteger::class)]
#[UsesClass(PdfName::class)]
#[UsesClass(PdfNull::class)]
#[UsesClass(PdfString::class)]
#[UsesClass(PdfLexer::class)]
#[UsesClass(PdfObjectParser::class)]
#[UsesClass(PdfReadDocument::class)]
#[UsesClass(PdfToken::class)]
#[UsesClass(PdfXRefTable::class)]
final class SetTextTest extends TestCase
{
    #[Test]
    public function setTextModifiesFieldAndAppendsIncrementalUpdate(): void
    {
        // Arrange
        [$document, $originalBytes] = self::buildPdfWithTextField();
        $reader = new PdfAcroFormReader($document);
        $fields = $reader->getFields();
        self::assertCount(1, $fields);

        $filler = new PdfAcroFormFiller($document, $originalBytes);

        // Act
        $filler->setText($fields[0], 'John Doe');
        $result = $filler->getBytes();

        // Assert — result is longer than original (incremental update appended)
        self::assertGreaterThan(strlen($originalBytes), strlen($result));
        self::assertStringContainsString('John Doe', $result);
    }

    #[Test]
    public function setCheckedOnButtonField(): void
    {
        // Arrange — build PDF with a checkbox field (Btn type)
        $header = "%PDF-1.4\n";
        $body = '';

        $off1 = strlen($header) + strlen($body);
        $body .= "1 0 obj\n<< /Type /Catalog /Pages 2 0 R /AcroForm 4 0 R >>\nendobj\n";

        $off2 = strlen($header) + strlen($body);
        $body .= "2 0 obj\n<< /Type /Pages /Kids [3 0 R] /Count 1 >>\nendobj\n";

        $off3 = strlen($header) + strlen($body);
        $body .= "3 0 obj\n<< /Type /Page /Parent 2 0 R /MediaBox [0 0 595 842] >>\nendobj\n";

        $off5 = strlen($header) + strlen($body);
        $body .= "5 0 obj\n<< /FT /Btn /T (check) /V /Off >>\nendobj\n";

        $off4 = strlen($header) + strlen($body);
        $body .= "4 0 obj\n<< /Fields [5 0 R] >>\nendobj\n";

        $xrefOffset = strlen($header) + strlen($body);
        $content = $header . $body;

        $content .= "xref\n0 6\n";
        $content .= "0000000000 65535 f \n";
        $content .= sprintf("%010d 00000 n \n", $off1);
        $content .= sprintf("%010d 00000 n \n", $off2);
        $content .= sprintf("%010d 00000 n \n", $off3);
        $content .= sprintf("%010d 00000 n \n", $off4);
        $content .= sprintf("%010d 00000 n \n", $off5);
        $content .= "trailer\n<< /Size 6 /Root 1 0 R >>\n";
        $content .= "startxref\n{$xrefOffset}\n%%EOF\n";

        $lexer = PdfLexer::fromString($content);
        $startXRef = $lexer->findStartXRef();
        [$xref, $trailer] = (new PdfXRefTable($lexer))->parse($startXRef);
        $document = new PdfReadDocument($lexer, $xref, $trailer, PdfVersion::PDF_1_4, null, $startXRef);

        $reader = new PdfAcroFormReader($document);
        $fields = $reader->getFields();
        $filler = new PdfAcroFormFiller($document, $content);

        // Act
        $filler->setChecked($fields[0], true);
        $result = $filler->getBytes();

        // Assert
        self::assertStringContainsString('/Yes', $result);
    }

    #[Test]
    public function setCheckedToFalseUsesOffStateName(): void
    {
        // Arrange — build PDF with a checkbox field (Btn type), currently checked
        $header = "%PDF-1.4\n";
        $body = '';

        $off1 = strlen($header) + strlen($body);
        $body .= "1 0 obj\n<< /Type /Catalog /Pages 2 0 R /AcroForm 4 0 R >>\nendobj\n";

        $off2 = strlen($header) + strlen($body);
        $body .= "2 0 obj\n<< /Type /Pages /Kids [3 0 R] /Count 1 >>\nendobj\n";

        $off3 = strlen($header) + strlen($body);
        $body .= "3 0 obj\n<< /Type /Page /Parent 2 0 R /MediaBox [0 0 595 842] >>\nendobj\n";

        $off5 = strlen($header) + strlen($body);
        $body .= "5 0 obj\n<< /FT /Btn /T (check) /V /Yes >>\nendobj\n";

        $off4 = strlen($header) + strlen($body);
        $body .= "4 0 obj\n<< /Fields [5 0 R] >>\nendobj\n";

        $xrefOffset = strlen($header) + strlen($body);
        $content = $header . $body;

        $content .= "xref\n0 6\n";
        $content .= "0000000000 65535 f \n";
        $content .= sprintf("%010d 00000 n \n", $off1);
        $content .= sprintf("%010d 00000 n \n", $off2);
        $content .= sprintf("%010d 00000 n \n", $off3);
        $content .= sprintf("%010d 00000 n \n", $off4);
        $content .= sprintf("%010d 00000 n \n", $off5);
        $content .= "trailer\n<< /Size 6 /Root 1 0 R >>\n";
        $content .= "startxref\n{$xrefOffset}\n%%EOF\n";

        $lexer = PdfLexer::fromString($content);
        $startXRef = $lexer->findStartXRef();
        [$xref, $trailer] = (new PdfXRefTable($lexer))->parse($startXRef);
        $document = new PdfReadDocument($lexer, $xref, $trailer, PdfVersion::PDF_1_4, null, $startXRef);

        $reader = new PdfAcroFormReader($document);
        $fields = $reader->getFields();
        $filler = new PdfAcroFormFiller($document, $content);

        // Act — uncheck the checkbox
        $filler->setChecked($fields[0], false);
        $result = $filler->getBytes();

        // Assert — the /Off state name was used for /V and /AS
        self::assertStringContainsString('/Off', $result);
    }

    /** @return array{\PhpPdf\Reader\PdfReadDocument, string} */
    private static function buildPdfWithTextField(): array
    {
        $header = "%PDF-1.4\n";
        $body = '';

        // obj 1: Catalog (with AcroForm)
        $off1 = strlen($header) + strlen($body);
        $body .= "1 0 obj\n<< /Type /Catalog /Pages 2 0 R /AcroForm 4 0 R >>\nendobj\n";

        // obj 2: Pages
        $off2 = strlen($header) + strlen($body);
        $body .= "2 0 obj\n<< /Type /Pages /Kids [3 0 R] /Count 1 >>\nendobj\n";

        // obj 3: Page
        $off3 = strlen($header) + strlen($body);
        $body .= "3 0 obj\n<< /Type /Page /Parent 2 0 R /MediaBox [0 0 595 842] >>\nendobj\n";

        // obj 5: text field
        $off5 = strlen($header) + strlen($body);
        $body .= "5 0 obj\n<< /FT /Tx /T (name) /V () >>\nendobj\n";

        // obj 4: AcroForm
        $off4 = strlen($header) + strlen($body);
        $body .= "4 0 obj\n<< /Fields [5 0 R] >>\nendobj\n";

        $xrefOffset = strlen($header) + strlen($body);
        $content = $header . $body;

        $content .= "xref\n0 6\n";
        $content .= "0000000000 65535 f \n";
        $content .= sprintf("%010d 00000 n \n", $off1);
        $content .= sprintf("%010d 00000 n \n", $off2);
        $content .= sprintf("%010d 00000 n \n", $off3);
        $content .= sprintf("%010d 00000 n \n", $off4);
        $content .= sprintf("%010d 00000 n \n", $off5);
        $content .= "trailer\n<< /Size 6 /Root 1 0 R >>\n";
        $content .= "startxref\n{$xrefOffset}\n%%EOF\n";

        $lexer = PdfLexer::fromString($content);
        $startXRef = $lexer->findStartXRef();
        [$xref, $trailer] = (new PdfXRefTable($lexer))->parse($startXRef);
        $document = new PdfReadDocument($lexer, $xref, $trailer, PdfVersion::PDF_1_4, null, $startXRef);

        return [$document, $content];
    }
}
