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
final class FormTreeTest extends TestCase
{
    #[Test]
    public function walksNestedKidsToLeafFields(): void
    {
        // Arrange — parent node (obj 4) has /Kids pointing to child (obj 5 and 6)
        // obj 4: AcroForm with Fields = [7 0 R]
        // obj 7: parent group with /T (group) and /Kids [5 0 R 6 0 R]
        // obj 5: leaf text field
        // obj 6: leaf text field
        [$document] = self::buildPdf(
            // obj 4: AcroForm
            "4 0 obj\n<< /Fields [7 0 R] >>\nendobj\n",
            // obj 5: child text field 1
            "5 0 obj\n<< /FT /Tx /T (first) /V (hello) >>\nendobj\n",
            // obj 6: child text field 2
            "6 0 obj\n<< /FT /Tx /T (second) /V (world) >>\nendobj\n",
            // obj 7: parent group node
            "7 0 obj\n<< /T (group) /Kids [5 0 R 6 0 R] >>\nendobj\n",
        );

        $reader = new PdfAcroFormReader($document);

        // Act
        $fields = $reader->getFields();

        // Assert — two leaf fields
        self::assertCount(2, $fields);
        $names = array_map(static fn ($f) => $f->fullName, $fields);
        self::assertContains('group.first', $names);
        self::assertContains('group.second', $names);
    }

    #[Test]
    public function buttonFieldCheckedReturnsTrue(): void
    {
        // Arrange — checkbox with /V /Yes
        [$document] = self::buildPdf(
            "4 0 obj\n<< /Fields [5 0 R] >>\nendobj\n",
            "5 0 obj\n<< /FT /Btn /T (agree) /V /Yes >>\nendobj\n",
        );

        $reader = new PdfAcroFormReader($document);

        // Act
        $fields = $reader->getFields();

        // Assert
        self::assertCount(1, $fields);
        self::assertSame(PdfFormFieldType::Button, $fields[0]->type);
        self::assertTrue($fields[0]->value);
    }

    #[Test]
    public function buttonFieldUncheckedReturnsFalse(): void
    {
        // Arrange — checkbox with /V /Off
        [$document] = self::buildPdf(
            "4 0 obj\n<< /Fields [5 0 R] >>\nendobj\n",
            "5 0 obj\n<< /FT /Btn /T (agree) /V /Off >>\nendobj\n",
        );

        $reader = new PdfAcroFormReader($document);

        // Act
        $fields = $reader->getFields();

        // Assert
        self::assertCount(1, $fields);
        self::assertFalse($fields[0]->value);
    }

    #[Test]
    public function choiceFieldWithStringOptions(): void
    {
        // Arrange — combo box with /Opt [(Red) (Green) (Blue)]
        [$document] = self::buildPdf(
            "4 0 obj\n<< /Fields [5 0 R] >>\nendobj\n",
            "5 0 obj\n<< /FT /Ch /T (color) /V (Red) /Opt [(Red) (Green) (Blue)] >>\nendobj\n",
        );

        $reader = new PdfAcroFormReader($document);

        // Act
        $fields = $reader->getFields();

        // Assert
        self::assertCount(1, $fields);
        $field = $fields[0];
        self::assertSame(PdfFormFieldType::Choice, $field->type);
        self::assertSame(['Red', 'Green', 'Blue'], $field->options);
    }

    #[Test]
    public function leafFieldWithNoFtIsSkipped(): void
    {
        // Arrange — field dict with no /FT and no inherited type
        [$document] = self::buildPdf(
            "4 0 obj\n<< /Fields [5 0 R] >>\nendobj\n",
            "5 0 obj\n<< /T (unknown) >>\nendobj\n",
        );

        $reader = new PdfAcroFormReader($document);

        // Act
        $fields = $reader->getFields();

        // Assert — field without /FT is skipped
        self::assertSame([], $fields);
    }

    #[Test]
    public function fieldWithInheritedFtFromParent(): void
    {
        // Arrange — parent carries /FT, child inherits it
        [$document] = self::buildPdf(
            "4 0 obj\n<< /Fields [6 0 R] >>\nendobj\n",
            // obj 5: child leaf with no /FT
            "5 0 obj\n<< /T (child) /V (value) >>\nendobj\n",
            // obj 6: parent with /FT and /Kids
            "6 0 obj\n<< /FT /Tx /T (parent) /Kids [5 0 R] >>\nendobj\n",
        );

        $reader = new PdfAcroFormReader($document);

        // Act
        $fields = $reader->getFields();

        // Assert — child inherits /Tx from parent
        self::assertCount(1, $fields);
        self::assertSame(PdfFormFieldType::Text, $fields[0]->type);
        self::assertSame('parent.child', $fields[0]->fullName);
    }

    #[Test]
    public function readOnlyFlagIsDetected(): void
    {
        // Arrange — /Ff 1 (bit 0 = read only)
        [$document] = self::buildPdf(
            "4 0 obj\n<< /Fields [5 0 R] >>\nendobj\n",
            "5 0 obj\n<< /FT /Tx /T (name) /Ff 1 >>\nendobj\n",
        );

        $reader = new PdfAcroFormReader($document);

        // Act
        $fields = $reader->getFields();

        // Assert
        self::assertCount(1, $fields);
        self::assertTrue($fields[0]->readOnly);
    }

    #[Test]
    public function multiLineFlagIsDetected(): void
    {
        // Arrange — /Ff 4096 (bit 12 = multi-line, 0x1000 = 4096)
        [$document] = self::buildPdf(
            "4 0 obj\n<< /Fields [5 0 R] >>\nendobj\n",
            "5 0 obj\n<< /FT /Tx /T (notes) /Ff 4096 >>\nendobj\n",
        );

        $reader = new PdfAcroFormReader($document);

        // Act
        $fields = $reader->getFields();

        // Assert
        self::assertCount(1, $fields);
        self::assertTrue($fields[0]->multiLine);
    }

    #[Test]
    public function fieldsArrayItemThatIsNotIndirectReferenceUsesZeroObjectNumbers(): void
    {
        // Arrange — /Fields contains a direct dictionary (not an indirect reference)
        // (lines 87/90: objNum/gen default to 0 when item is not PdfIndirectReference)
        [$document] = self::buildPdf(
            "4 0 obj\n<< /Fields [<< /FT /Tx /T (direct) /V (val) >>] >>\nendobj\n",
        );

        $reader = new PdfAcroFormReader($document);

        // Act
        $fields = $reader->getFields();

        // Assert
        self::assertCount(1, $fields);
        self::assertSame(0, $fields[0]->objectNumber);
        self::assertSame(0, $fields[0]->generationNumber);
    }

    #[Test]
    public function fieldsArrayItemResolvingToNonDictionaryIsSkipped(): void
    {
        // Arrange — /Fields contains a direct integer (not a dictionary) (line 94: continue)
        [$document] = self::buildPdf(
            "4 0 obj\n<< /Fields [42] >>\nendobj\n",
        );

        $reader = new PdfAcroFormReader($document);

        // Act
        $fields = $reader->getFields();

        // Assert — non-dictionary item is skipped
        self::assertSame([], $fields);
    }

    #[Test]
    public function fieldWithoutTUsesEmptyPartialName(): void
    {
        // Arrange — field dict with no /T entry (line 114: partialName = '')
        [$document] = self::buildPdf(
            "4 0 obj\n<< /Fields [5 0 R] >>\nendobj\n",
            "5 0 obj\n<< /FT /Tx /V (hello) >>\nendobj\n",
        );

        $reader = new PdfAcroFormReader($document);

        // Act
        $fields = $reader->getFields();

        // Assert — partial and full name are empty
        self::assertCount(1, $fields);
        self::assertSame('', $fields[0]->name);
        self::assertSame('', $fields[0]->fullName);
    }

    #[Test]
    public function kidsArrayItemThatIsNotIndirectReferenceUsesZeroObjectNumbers(): void
    {
        // Arrange — /Kids contains a direct dictionary (not an indirect reference)
        // (lines 134/137: kidObjNum/kidGen default to 0 when kidRef is not PdfIndirectReference)
        [$document] = self::buildPdf(
            "4 0 obj\n<< /Fields [5 0 R] >>\nendobj\n",
            "5 0 obj\n<< /T (group) /Kids [<< /FT /Tx /T (child) /V (val) >>] >>\nendobj\n",
        );

        $reader = new PdfAcroFormReader($document);

        // Act
        $fields = $reader->getFields();

        // Assert
        self::assertCount(1, $fields);
        self::assertSame(0, $fields[0]->objectNumber);
        self::assertSame(0, $fields[0]->generationNumber);
        self::assertSame('group.child', $fields[0]->fullName);
    }

    #[Test]
    public function kidsArrayItemResolvingToNonDictionaryIsSkipped(): void
    {
        // Arrange — /Kids contains a direct integer (not a dictionary) (line 141: continue)
        [$document] = self::buildPdf(
            "4 0 obj\n<< /Fields [5 0 R] >>\nendobj\n",
            "5 0 obj\n<< /T (group) /Kids [42] >>\nendobj\n",
        );

        $reader = new PdfAcroFormReader($document);

        // Act
        $fields = $reader->getFields();

        // Assert — non-dictionary kid is skipped
        self::assertSame([], $fields);
    }

    /** @return array{\PhpPdf\Reader\PdfReadDocument, string} */
    private static function buildPdf(string ...$extraObjects): array
    {
        $header = "%PDF-1.4\n";
        $body = '';
        $offsets = [];

        $allObjs = array_merge([
            "1 0 obj\n<< /Type /Catalog /Pages 2 0 R /AcroForm 4 0 R >>\nendobj\n",
            "2 0 obj\n<< /Type /Pages /Kids [3 0 R] /Count 1 >>\nendobj\n",
            "3 0 obj\n<< /Type /Page /Parent 2 0 R /MediaBox [0 0 595 842] >>\nendobj\n",
        ], $extraObjects);

        foreach (array_values($allObjs) as $i => $obj) {
            $offsets[$i + 1] = strlen($header) + strlen($body);
            $body .= $obj;
        }

        $xrefOffset = strlen($header) + strlen($body);
        $n = count($allObjs) + 1;
        $content = $header . $body;
        $content .= "xref\n0 {$n}\n";
        $content .= "0000000000 65535 f \n";

        for ($i = 1; $i < $n; $i++) {
            $content .= sprintf("%010d 00000 n \n", $offsets[$i]);
        }

        $content .= "trailer\n<< /Size {$n} /Root 1 0 R >>\n";
        $content .= "startxref\n{$xrefOffset}\n%%EOF\n";

        $lexer = PdfLexer::fromString($content);
        $startXRef = $lexer->findStartXRef();
        [$xref, $trailer] = (new PdfXRefTable($lexer))->parse($startXRef);

        return [new PdfReadDocument($lexer, $xref, $trailer, PdfVersion::PDF_1_4, null, $startXRef), $content];
    }
}
