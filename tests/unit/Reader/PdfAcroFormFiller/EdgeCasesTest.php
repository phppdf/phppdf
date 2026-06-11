<?php

declare(strict_types=1);

namespace PhpPdf\Reader\PdfAcroFormFiller;

use PhpPdf\Encryption\PdfEncryptionContext;
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
use RuntimeException;

#[CoversClass(PdfAcroFormFiller::class)]
#[CoversMethod(PdfAcroFormFiller::class, 'setText')]
#[CoversMethod(PdfAcroFormFiller::class, 'setChecked')]
#[CoversMethod(PdfAcroFormFiller::class, 'setChoice')]
#[CoversMethod(PdfAcroFormFiller::class, 'save')]
#[CoversMethod(PdfAcroFormFiller::class, 'getBytes')]
#[UsesClass(PdfAcroFormReader::class)]
#[UsesClass(PdfArray::class)]
#[UsesClass(PdfBoolean::class)]
#[UsesClass(PdfDictionary::class)]
#[UsesClass(PdfEncryptionContext::class)]
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
final class EdgeCasesTest extends TestCase
{
    #[Test]
    public function saveThrowsRuntimeExceptionOnWriteFailure(): void
    {
        // Arrange
        [$document, $originalBytes] = self::buildSimplePdfWithTextField();
        $reader = new PdfAcroFormReader($document);
        $fields = $reader->getFields();
        $filler = new PdfAcroFormFiller($document, $originalBytes);
        $filler->setText($fields[0], 'test');

        // Suppress the PHP warning from file_put_contents so failOnWarning config
        // does not abort the test. We verify the RuntimeException is still thrown.
        set_error_handler(static fn () => true);

        try {
            $this->expectException(RuntimeException::class);
            $filler->save('/nonexistent_directory_that_cannot_exist/output.pdf');
        } finally {
            restore_error_handler();
        }
    }

    #[Test]
    public function setTextIgnoresFieldWithObjNumNotInXref(): void
    {
        // Arrange — field with objectNumber that is NOT in the xref (no xref entry)
        [$document, $originalBytes] = self::buildSimplePdfWithTextField();
        $filler = new PdfAcroFormFiller($document, $originalBytes);

        $field = new PdfFormField(
            objectNumber: 999, // not in xref
            generationNumber: 0,
            name: 'phantom',
            fullName: 'phantom',
            type: PdfFormFieldType::Text,
            value: null,
        );

        // Act
        $filler->setText($field, 'value');
        $result = $filler->getBytes();

        // Assert — no modification; returns original bytes
        self::assertSame($originalBytes, $result);
    }

    #[Test]
    public function setTextIgnoresFieldWithNonNormalXrefEntry(): void
    {
        // Arrange — manually build a document where obj 5 has type='s' in xref
        $header = "%PDF-1.4\n";
        $body = '';
        $off1 = strlen($header) + strlen($body);
        $body .= "1 0 obj\n<< /Type /Catalog /Pages 2 0 R /AcroForm 4 0 R >>\nendobj\n";
        $off2 = strlen($header) + strlen($body);
        $body .= "2 0 obj\n<< /Type /Pages /Kids [] /Count 0 >>\nendobj\n";
        $off4 = strlen($header) + strlen($body);
        $body .= "4 0 obj\n<< /Fields [] >>\nendobj\n";

        $xrefOffset = strlen($header) + strlen($body);
        $content = $header . $body;
        $content .= "xref\n0 5\n";
        $content .= "0000000000 65535 f \n";
        $content .= sprintf("%010d 00000 n \n", $off1);
        $content .= sprintf("%010d 00000 n \n", $off2);
        $content .= "0000000000 65535 f \n"; // obj 3 free
        $content .= sprintf("%010d 00000 n \n", $off4);
        $content .= "trailer\n<< /Size 5 /Root 1 0 R >>\n";
        $content .= "startxref\n{$xrefOffset}\n%%EOF\n";

        $lexer = PdfLexer::fromString($content);
        $startXRef = $lexer->findStartXRef();
        [$xref, $trailer] = (new PdfXRefTable($lexer))->parse($startXRef);

        // Manually add a 'f' (free) type entry for obj 5
        $xref[5] = ['offset' => 0, 'generation' => 0, 'type' => 'f'];
        $document = new PdfReadDocument($lexer, $xref, $trailer, PdfVersion::PDF_1_4, null, $startXRef);

        $filler = new PdfAcroFormFiller($document, $content);
        $field = new PdfFormField(
            objectNumber: 5,
            generationNumber: 0,
            name: 'f',
            fullName: 'f',
            type: PdfFormFieldType::Text,
            value: null,
        );

        // Act
        $filler->setText($field, 'value');
        $result = $filler->getBytes();

        // Assert — type='f' → getOrCloneDict returns null → no modification
        self::assertSame($content, $result);
    }

    #[Test]
    public function setCheckedReturnsEarlyWhenObjNumIsZero(): void
    {
        // Arrange — field with objectNumber=0 → getOrCloneDict returns null (line 77)
        [$document, $originalBytes] = self::buildSimplePdfWithTextField();
        $filler = new PdfAcroFormFiller($document, $originalBytes);

        $field = new PdfFormField(
            objectNumber: 0,
            generationNumber: 0,
            name: 'cb',
            fullName: 'cb',
            type: PdfFormFieldType::Button,
            value: false,
        );

        // Act
        $filler->setChecked($field, true);
        $result = $filler->getBytes();

        // Assert — no modification; getOrCloneDict returns null for objNum=0
        self::assertSame($originalBytes, $result);
    }

    #[Test]
    public function setChoiceReturnsEarlyWhenObjNumIsZero(): void
    {
        // Arrange — field with objectNumber=0 → getOrCloneDict returns null (line 94)
        [$document, $originalBytes] = self::buildSimplePdfWithTextField();
        $filler = new PdfAcroFormFiller($document, $originalBytes);

        $field = new PdfFormField(
            objectNumber: 0,
            generationNumber: 0,
            name: 'ch',
            fullName: 'ch',
            type: PdfFormFieldType::Choice,
            value: null,
        );

        // Act
        $filler->setChoice($field, 'option1');
        $result = $filler->getBytes();

        // Assert — no modification
        self::assertSame($originalBytes, $result);
    }

    #[Test]
    public function getOrCloneDictReturnsCachedModification(): void
    {
        // Arrange — call setText twice on the same field to exercise the cached
        // modification path (line 181: return $this->modifications[$objNum])
        [$document, $originalBytes] = self::buildSimplePdfWithTextField();
        $reader = new PdfAcroFormReader($document);
        $fields = $reader->getFields();
        $filler = new PdfAcroFormFiller($document, $originalBytes);

        // Act — first call clones the dict; second call returns cached clone
        $filler->setText($fields[0], 'first');
        $filler->setText($fields[0], 'second');
        $result = $filler->getBytes();

        // Assert — second value wins (cached dict was mutated again)
        self::assertStringContainsString('second', $result);
    }

    #[Test]
    public function getOrCloneDictReturnsNullWhenObjectIsNotDictionary(): void
    {
        // Arrange — xref entry for obj 5 exists with type='n', but the object
        // at that offset is not a PdfDictionary (it's an integer) → line 189
        $header = "%PDF-1.4\n";
        $body = '';

        $off1 = strlen($header) + strlen($body);
        $body .= "1 0 obj\n<< /Type /Catalog /Pages 2 0 R /AcroForm 4 0 R >>\nendobj\n";
        $off2 = strlen($header) + strlen($body);
        $body .= "2 0 obj\n<< /Type /Pages /Kids [3 0 R] /Count 1 >>\nendobj\n";
        $off3 = strlen($header) + strlen($body);
        $body .= "3 0 obj\n<< /Type /Page /Parent 2 0 R /MediaBox [0 0 595 842] >>\nendobj\n";
        $off5 = strlen($header) + strlen($body);
        $body .= "5 0 obj\n42\nendobj\n"; // integer, not a dict
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

        $filler = new PdfAcroFormFiller($document, $content);
        $field = new PdfFormField(
            objectNumber: 5,
            generationNumber: 0,
            name: 'f',
            fullName: 'f',
            type: PdfFormFieldType::Text,
            value: null,
        );

        // Act
        $filler->setText($field, 'value');
        $result = $filler->getBytes();

        // Assert — object at obj 5 is not a dict → returns null → no modification
        self::assertSame($content, $result);
    }

    #[Test]
    public function loadDictForObjectReturnsNullWhenXrefEntryMissing(): void
    {
        // Arrange — build a PDF where AcroForm object number is present in catalog
        // but NOT in xref, so loadDictForObject returns null (line 201)
        $header = "%PDF-1.4\n";
        $body = '';

        $off1 = strlen($header) + strlen($body);
        $body .= "1 0 obj\n<< /Type /Catalog /Pages 2 0 R /AcroForm 10 0 R >>\nendobj\n";
        $off2 = strlen($header) + strlen($body);
        $body .= "2 0 obj\n<< /Type /Pages /Kids [3 0 R] /Count 1 >>\nendobj\n";
        $off3 = strlen($header) + strlen($body);
        $body .= "3 0 obj\n<< /Type /Page /Parent 2 0 R /MediaBox [0 0 595 842] >>\nendobj\n";
        $off5 = strlen($header) + strlen($body);
        $body .= "5 0 obj\n<< /FT /Tx /T (name) /V () >>\nendobj\n";
        $off4 = strlen($header) + strlen($body);
        $body .= "4 0 obj\n<< /Fields [5 0 R] >>\nendobj\n";
        // obj 10 (AcroForm) intentionally NOT added to xref

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

        $filler = new PdfAcroFormFiller($document, $content);
        $field = new PdfFormField(
            objectNumber: 5,
            generationNumber: 0,
            name: 'name',
            fullName: 'name',
            type: PdfFormFieldType::Text,
            value: null,
        );

        // Act — setText on field 5; AcroForm obj 10 not in xref → loadDictForObject null
        $filler->setText($field, 'hello');
        $result = $filler->getBytes();

        // Assert — field 5 was modified but AcroForm obj was skipped (not in xref)
        self::assertGreaterThan(strlen($content), strlen($result));
        self::assertStringContainsString('hello', $result);
        // AcroForm NeedAppearances patch was not added (obj 10 missing from xref)
        self::assertStringNotContainsString('NeedAppearances', $result);
    }

    #[Test]
    public function loadDictForObjectReturnsNullWhenObjectIsNotDict(): void
    {
        // Arrange — AcroForm object exists in xref but resolves to integer (line 206)
        $header = "%PDF-1.4\n";
        $body = '';

        $off1 = strlen($header) + strlen($body);
        $body .= "1 0 obj\n<< /Type /Catalog /Pages 2 0 R /AcroForm 10 0 R >>\nendobj\n";
        $off2 = strlen($header) + strlen($body);
        $body .= "2 0 obj\n<< /Type /Pages /Kids [3 0 R] /Count 1 >>\nendobj\n";
        $off3 = strlen($header) + strlen($body);
        $body .= "3 0 obj\n<< /Type /Page /Parent 2 0 R /MediaBox [0 0 595 842] >>\nendobj\n";
        $off5 = strlen($header) + strlen($body);
        $body .= "5 0 obj\n<< /FT /Tx /T (name) /V () >>\nendobj\n";
        $off4 = strlen($header) + strlen($body);
        $body .= "4 0 obj\n<< /Fields [5 0 R] >>\nendobj\n";
        $off10 = strlen($header) + strlen($body);
        $body .= "10 0 obj\n99\nendobj\n"; // integer, not a dict

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

        // Manually inject obj 10 into xref
        $lexer = PdfLexer::fromString($content);
        $startXRef = $lexer->findStartXRef();
        [$xref, $trailer] = (new PdfXRefTable($lexer))->parse($startXRef);
        $xref[10] = ['offset' => $off10, 'generation' => 0, 'type' => 'n'];
        $document = new PdfReadDocument($lexer, $xref, $trailer, PdfVersion::PDF_1_4, null, $startXRef);

        $filler = new PdfAcroFormFiller($document, $content);
        $field = new PdfFormField(
            objectNumber: 5,
            generationNumber: 0,
            name: 'name',
            fullName: 'name',
            type: PdfFormFieldType::Text,
            value: null,
        );

        // Act — AcroForm obj 10 is integer → loadDictForObject returns null
        $filler->setText($field, 'hello');
        $result = $filler->getBytes();

        // Assert — field was modified; AcroForm NeedAppearances patch was skipped
        self::assertGreaterThan(strlen($content), strlen($result));
        self::assertStringNotContainsString('NeedAppearances', $result);
    }

    #[Test]
    public function getAcroFormObjectNumberReturnsZeroWhenAcroFormIsDirect(): void
    {
        // Arrange — build PDF where /AcroForm is a direct dict (not an indirect ref)
        // so getAcroFormObjectNumber returns 0 (line 224)
        $header = "%PDF-1.4\n";
        $body = '';

        $off1 = strlen($header) + strlen($body);
        $body .= "1 0 obj\n<< /Type /Catalog /Pages 2 0 R /AcroForm << /Fields [5 0 R] >> >>\nendobj\n";
        $off2 = strlen($header) + strlen($body);
        $body .= "2 0 obj\n<< /Type /Pages /Kids [3 0 R] /Count 1 >>\nendobj\n";
        $off3 = strlen($header) + strlen($body);
        $body .= "3 0 obj\n<< /Type /Page /Parent 2 0 R /MediaBox [0 0 595 842] >>\nendobj\n";
        $off5 = strlen($header) + strlen($body);
        $body .= "5 0 obj\n<< /FT /Tx /T (name) /V () >>\nendobj\n";

        $xrefOffset = strlen($header) + strlen($body);
        $content = $header . $body;

        $content .= "xref\n0 6\n";
        $content .= "0000000000 65535 f \n";
        $content .= sprintf("%010d 00000 n \n", $off1);
        $content .= sprintf("%010d 00000 n \n", $off2);
        $content .= sprintf("%010d 00000 n \n", $off3);
        $content .= "0000000000 65535 f \n";
        $content .= sprintf("%010d 00000 n \n", $off5);
        $content .= "trailer\n<< /Size 6 /Root 1 0 R >>\n";
        $content .= "startxref\n{$xrefOffset}\n%%EOF\n";

        $lexer = PdfLexer::fromString($content);
        $startXRef = $lexer->findStartXRef();
        [$xref, $trailer] = (new PdfXRefTable($lexer))->parse($startXRef);
        $document = new PdfReadDocument($lexer, $xref, $trailer, PdfVersion::PDF_1_4, null, $startXRef);

        $filler = new PdfAcroFormFiller($document, $content);
        $field = new PdfFormField(
            objectNumber: 5,
            generationNumber: 0,
            name: 'name',
            fullName: 'name',
            type: PdfFormFieldType::Text,
            value: null,
        );

        // Act — getAcroFormObjectNumber returns 0 (direct /AcroForm)
        $filler->setText($field, 'hello');
        $result = $filler->getBytes();

        // Assert — field was modified; no AcroForm NeedAppearances patch (objNum=0)
        self::assertGreaterThan(strlen($content), strlen($result));
        self::assertStringContainsString('hello', $result);
    }

    #[Test]
    public function buildXRefCreatesMultipleGroupsForNonConsecutiveObjects(): void
    {
        // Arrange — modify two non-consecutive objects so buildXRef creates
        // multiple subsection groups (line 309: $groups[] = $group)
        $header = "%PDF-1.4\n";
        $body = '';

        $off1 = strlen($header) + strlen($body);
        $body .= "1 0 obj\n<< /Type /Catalog /Pages 2 0 R /AcroForm 4 0 R >>\nendobj\n";
        $off2 = strlen($header) + strlen($body);
        $body .= "2 0 obj\n<< /Type /Pages /Kids [3 0 R] /Count 1 >>\nendobj\n";
        $off3 = strlen($header) + strlen($body);
        $body .= "3 0 obj\n<< /Type /Page /Parent 2 0 R /MediaBox [0 0 595 842] >>\nendobj\n";
        $off5 = strlen($header) + strlen($body);
        $body .= "5 0 obj\n<< /FT /Tx /T (field1) /V () >>\nendobj\n";
        $off7 = strlen($header) + strlen($body);
        $body .= "7 0 obj\n<< /FT /Tx /T (field2) /V () >>\nendobj\n";
        $off4 = strlen($header) + strlen($body);
        $body .= "4 0 obj\n<< /Fields [5 0 R 7 0 R] >>\nendobj\n";

        $xrefOffset = strlen($header) + strlen($body);
        $content = $header . $body;

        $content .= "xref\n0 8\n";
        $content .= "0000000000 65535 f \n";
        $content .= sprintf("%010d 00000 n \n", $off1);
        $content .= sprintf("%010d 00000 n \n", $off2);
        $content .= sprintf("%010d 00000 n \n", $off3);
        $content .= sprintf("%010d 00000 n \n", $off4);
        $content .= sprintf("%010d 00000 n \n", $off5);
        $content .= "0000000000 65535 f \n"; // obj 6 free
        $content .= sprintf("%010d 00000 n \n", $off7);
        $content .= "trailer\n<< /Size 8 /Root 1 0 R >>\n";
        $content .= "startxref\n{$xrefOffset}\n%%EOF\n";

        $lexer = PdfLexer::fromString($content);
        $startXRef = $lexer->findStartXRef();
        [$xref, $trailer] = (new PdfXRefTable($lexer))->parse($startXRef);
        $document = new PdfReadDocument($lexer, $xref, $trailer, PdfVersion::PDF_1_4, null, $startXRef);

        $reader = new PdfAcroFormReader($document);
        $fields = $reader->getFields();
        $filler = new PdfAcroFormFiller($document, $content);

        // Modify both non-consecutive fields (obj 5 and obj 7)
        $filler->setText($fields[0], 'value1');
        $filler->setText($fields[1], 'value2');

        // Act
        $result = $filler->getBytes();

        // Assert — two separate xref subsections were written
        self::assertGreaterThan(strlen($content), strlen($result));
        self::assertStringContainsString('value1', $result);
        self::assertStringContainsString('value2', $result);
    }

    #[Test]
    public function buildTrailerIncludesInfoAndIdWhenPresent(): void
    {
        // Arrange — build PDF with /Info and /ID in trailer (lines 349, 353)
        $header = "%PDF-1.4\n";
        $body = '';

        $off1 = strlen($header) + strlen($body);
        $body .= "1 0 obj\n<< /Type /Catalog /Pages 2 0 R /AcroForm 4 0 R >>\nendobj\n";
        $off2 = strlen($header) + strlen($body);
        $body .= "2 0 obj\n<< /Type /Pages /Kids [3 0 R] /Count 1 >>\nendobj\n";
        $off3 = strlen($header) + strlen($body);
        $body .= "3 0 obj\n<< /Type /Page /Parent 2 0 R /MediaBox [0 0 595 842] >>\nendobj\n";
        $off5 = strlen($header) + strlen($body);
        $body .= "5 0 obj\n<< /FT /Tx /T (name) /V () >>\nendobj\n";
        $off4 = strlen($header) + strlen($body);
        $body .= "4 0 obj\n<< /Fields [5 0 R] >>\nendobj\n";
        $off6 = strlen($header) + strlen($body);
        $body .= "6 0 obj\n<< /Title (Test) >>\nendobj\n";

        $xrefOffset = strlen($header) + strlen($body);
        $content = $header . $body;

        $content .= "xref\n0 7\n";
        $content .= "0000000000 65535 f \n";
        $content .= sprintf("%010d 00000 n \n", $off1);
        $content .= sprintf("%010d 00000 n \n", $off2);
        $content .= sprintf("%010d 00000 n \n", $off3);
        $content .= sprintf("%010d 00000 n \n", $off4);
        $content .= sprintf("%010d 00000 n \n", $off5);
        $content .= sprintf("%010d 00000 n \n", $off6);
        // Include /Info and /ID in trailer
        $content .= "trailer\n<< /Size 7 /Root 1 0 R /Info 6 0 R /ID [(abc) (abc)] >>\n";
        $content .= "startxref\n{$xrefOffset}\n%%EOF\n";

        $lexer = PdfLexer::fromString($content);
        $startXRef = $lexer->findStartXRef();
        [$xref, $trailer] = (new PdfXRefTable($lexer))->parse($startXRef);
        $document = new PdfReadDocument($lexer, $xref, $trailer, PdfVersion::PDF_1_4, null, $startXRef);

        $reader = new PdfAcroFormReader($document);
        $fields = $reader->getFields();
        $filler = new PdfAcroFormFiller($document, $content);

        // Act
        $filler->setText($fields[0], 'hello');
        $result = $filler->getBytes();

        // Assert — incremental trailer contains /Info and /ID
        self::assertStringContainsString('/Info', $result);
        self::assertStringContainsString('/ID', $result);
    }

    #[Test]
    public function serializeStringEncryptsValueWhenEncryptionContextSet(): void
    {
        // Arrange — build a PdfReadDocument with an active encryption context (lines 264-265)
        $header = "%PDF-1.4\n";
        $body = '';

        $off1 = strlen($header) + strlen($body);
        $body .= "1 0 obj\n<< /Type /Catalog /Pages 2 0 R /AcroForm 4 0 R >>\nendobj\n";
        $off2 = strlen($header) + strlen($body);
        $body .= "2 0 obj\n<< /Type /Pages /Kids [3 0 R] /Count 1 >>\nendobj\n";
        $off3 = strlen($header) + strlen($body);
        $body .= "3 0 obj\n<< /Type /Page /Parent 2 0 R /MediaBox [0 0 595 842] >>\nendobj\n";
        $off5 = strlen($header) + strlen($body);
        $body .= "5 0 obj\n<< /FT /Tx /T (name) /V () >>\nendobj\n";
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

        // Create an encryption context that will encrypt object 5 (the text field)
        $encryptionContext = new PdfEncryptionContext(str_repeat("\xAB", 16));
        $document = new PdfReadDocument($lexer, $xref, $trailer, PdfVersion::PDF_1_4, $encryptionContext, $startXRef);

        $reader = new PdfAcroFormReader($document);
        $fields = $reader->getFields();
        $filler = new PdfAcroFormFiller($document, $content);

        // Act — setText on field 5; serializeString encrypts the value (lines 264-265)
        $filler->setText($fields[0], 'hello');
        $result = $filler->getBytes();

        // Assert — the /V value was encrypted (stored as hex string <...>)
        // Encrypted output is longer than original and contains no literal "(hello)"
        self::assertGreaterThan(strlen($content), strlen($result));
        self::assertStringNotContainsString('(hello)', $result);
        // The encrypted value is serialized as a hex string "<...>"
        self::assertStringContainsString('<', $result);
    }

    #[Test]
    public function fillerWithNameContainingSpecialCharEscapesIt(): void
    {
        // Arrange — build a PDF where field dict has a name value with '%' (encoded as #25)
        $header = "%PDF-1.4\n";
        $body = '';
        $off1 = strlen($header) + strlen($body);
        $body .= "1 0 obj\n<< /Type /Catalog /Pages 2 0 R /AcroForm 4 0 R >>\nendobj\n";
        $off2 = strlen($header) + strlen($body);
        $body .= "2 0 obj\n<< /Type /Pages /Kids [3 0 R] /Count 1 >>\nendobj\n";
        $off3 = strlen($header) + strlen($body);
        $body .= "3 0 obj\n<< /Type /Page /Parent 2 0 R /MediaBox [0 0 595 842] >>\nendobj\n";
        $off5 = strlen($header) + strlen($body);
        // Use #25 to encode '%' in a name (/AS value)
        $body .= "5 0 obj\n<< /FT /Tx /T (field) /V () /AS /Yes#25Off >>\nendobj\n";
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

        // Act — setText triggers serialization of the cloned dict
        // The /AS entry contains '%' (from #25) which triggers escapeName's callback
        $filler->setText($fields[0], 'hello');
        $result = $filler->getBytes();

        // Assert — result contains the escaped name (#25 encoded)
        self::assertGreaterThan(strlen($content), strlen($result));
    }

    /** @return array{\PhpPdf\Reader\PdfReadDocument, string} */
    private static function buildSimplePdfWithTextField(): array
    {
        $header = "%PDF-1.4\n";
        $body = '';

        $off1 = strlen($header) + strlen($body);
        $body .= "1 0 obj\n<< /Type /Catalog /Pages 2 0 R /AcroForm 4 0 R >>\nendobj\n";
        $off2 = strlen($header) + strlen($body);
        $body .= "2 0 obj\n<< /Type /Pages /Kids [3 0 R] /Count 1 >>\nendobj\n";
        $off3 = strlen($header) + strlen($body);
        $body .= "3 0 obj\n<< /Type /Page /Parent 2 0 R /MediaBox [0 0 595 842] >>\nendobj\n";
        $off5 = strlen($header) + strlen($body);
        $body .= "5 0 obj\n<< /FT /Tx /T (name) /V () >>\nendobj\n";
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
