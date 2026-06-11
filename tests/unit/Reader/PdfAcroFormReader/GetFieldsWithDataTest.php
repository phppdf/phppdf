<?php

declare(strict_types=1);

namespace PhpPdf\Reader\PdfAcroFormReader;

use PhpPdf\Object\PdfArray;
use PhpPdf\Object\PdfDictionary;
use PhpPdf\Object\PdfIndirectReference;
use PhpPdf\Object\PdfInteger;
use PhpPdf\Object\PdfName;
use PhpPdf\Object\PdfNull;
use PhpPdf\Object\PdfString;
use PhpPdf\Object\PdfVersion;
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

#[CoversClass(PdfAcroFormReader::class)]
#[CoversMethod(PdfAcroFormReader::class, 'getFields')]
#[UsesClass(PdfArray::class)]
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
final class GetFieldsWithDataTest extends TestCase
{
    #[Test]
    public function returnsTextFieldFromAcroForm(): void
    {
        // Arrange
        $document = self::buildPdfWithTextField();
        $reader = new PdfAcroFormReader($document);

        // Act
        $fields = $reader->getFields();

        // Assert
        self::assertCount(1, $fields);
        $field = $fields[0];
        self::assertSame(PdfFormFieldType::Text, $field->type);
        self::assertSame('name', $field->fullName);
        self::assertSame('John', $field->value);
    }

    #[Test]
    public function getFieldsByNameIndexesByFullName(): void
    {
        // Arrange
        $document = self::buildPdfWithTextField();
        $reader = new PdfAcroFormReader($document);

        // Act
        $map = $reader->getFieldsByName();

        // Assert
        self::assertArrayHasKey('name', $map);
        self::assertInstanceOf(PdfFormField::class, $map['name']);
    }

    private static function buildPdfWithTextField(): PdfReadDocument
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
        $body .= "5 0 obj\n<< /FT /Tx /T (name) /V (John) >>\nendobj\n";

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

        return new PdfReadDocument($lexer, $xref, $trailer, PdfVersion::PDF_1_4);
    }
}
