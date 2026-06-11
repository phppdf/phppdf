<?php

declare(strict_types=1);

namespace PhpPdf\Compliance\PdfAValidator;

use PhpPdf\Compliance\PdfAConformance;
use PhpPdf\Compliance\PdfAIssueLevel;
use PhpPdf\Compliance\PdfAValidationIssue;
use PhpPdf\Compliance\PdfAValidationResult;
use PhpPdf\Compliance\PdfAValidator;
use PhpPdf\Object\PdfArray;
use PhpPdf\Object\PdfBoolean;
use PhpPdf\Object\PdfDictionary;
use PhpPdf\Object\PdfIndirectReference;
use PhpPdf\Object\PdfInteger;
use PhpPdf\Object\PdfName;
use PhpPdf\Object\PdfObjectRegistry;
use PhpPdf\Object\PdfRawStreamData;
use PhpPdf\Object\PdfReal;
use PhpPdf\Object\PdfStream;
use PhpPdf\Object\PdfString;
use PhpPdf\Object\PdfVersion;
use PhpPdf\Reader\PdfDocumentReader;
use PhpPdf\Reader\PdfLexer;
use PhpPdf\Reader\PdfObjectParser;
use PhpPdf\Reader\PdfReadDocument;
use PhpPdf\Reader\PdfReadPage;
use PhpPdf\Reader\PdfToken;
use PhpPdf\Reader\PdfXRefTable;
use PhpPdf\Serialization\PdfStreamSerializer;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

#[CoversClass(PdfAValidator::class)]
#[CoversMethod(PdfAValidator::class, 'validate')]
#[CoversMethod(PdfAValidator::class, 'validateFile')]
#[UsesClass(PdfAConformance::class)]
#[UsesClass(PdfAIssueLevel::class)]
#[UsesClass(PdfAValidationIssue::class)]
#[UsesClass(PdfAValidationResult::class)]
#[UsesClass(PdfArray::class)]
#[UsesClass(PdfBoolean::class)]
#[UsesClass(PdfDictionary::class)]
#[UsesClass(PdfDocumentReader::class)]
#[UsesClass(PdfIndirectReference::class)]
#[UsesClass(PdfInteger::class)]
#[UsesClass(PdfLexer::class)]
#[UsesClass(PdfName::class)]
#[UsesClass(PdfObjectParser::class)]
#[UsesClass(PdfObjectRegistry::class)]
#[UsesClass(PdfRawStreamData::class)]
#[UsesClass(PdfReal::class)]
#[UsesClass(PdfReadDocument::class)]
#[UsesClass(PdfReadPage::class)]
#[UsesClass(PdfStream::class)]
#[UsesClass(PdfStreamSerializer::class)]
#[UsesClass(PdfString::class)]
#[UsesClass(PdfToken::class)]
#[UsesClass(PdfXRefTable::class)]
final class ValidateTest extends TestCase
{
    // =========================================================================
    // Raw-PDF factory helpers
    // =========================================================================

    #[Test]
    public function validateEmitsTrailerIdErrorWhenIdMissing(): void
    {
        $doc = self::makeDoc(
            [1 => '<</Type /Catalog /Pages 2 0 R>>', 2 => '<</Type /Pages /Kids [] /Count 0>>'],
            ['Root' => new PdfIndirectReference(1, 0)],
        );
        $result = (new PdfAValidator())->validate($doc, PdfAConformance::PdfA2b);

        self::assertHasError('trailer.id', $result);
    }

    #[Test]
    public function validateNoTrailerIdErrorWhenIdPresent(): void
    {
        $doc = self::minimalCompliantDoc(PdfAConformance::PdfA2b);
        $result = (new PdfAValidator())->validate($doc, PdfAConformance::PdfA2b);

        self::assertNoError('trailer.id', $result);
    }

    // =========================================================================
    // checkNoEncryption
    // =========================================================================

    #[Test]
    public function validateEmitsEncryptionErrorWhenEncryptInTrailer(): void
    {
        // Put a non-null 'Encrypt' value in the trailer — validator just checks != null
        $doc = self::makeDoc(
            [1 => '<</Type /Catalog /Pages 2 0 R>>', 2 => '<</Type /Pages /Kids [] /Count 0>>'],
            self::withId([
                'Encrypt' => new PdfName('Standard'), // any non-null value
                'Root' => new PdfIndirectReference(1, 0),
            ]),
        );

        $result = (new PdfAValidator())->validate($doc, PdfAConformance::PdfA2b);

        self::assertHasError('no-encryption', $result);
    }

    // =========================================================================
    // checkPdfVersion
    // =========================================================================

    #[Test]
    public function validateEmitsVersionWarnForPdf13WithPdfA1b(): void
    {
        $doc = self::makeDoc(
            [1 => '<</Type /Catalog /Pages 2 0 R>>', 2 => '<</Type /Pages /Kids [] /Count 0>>'],
            self::withId(['Root' => new PdfIndirectReference(1, 0)]),
            PdfVersion::PDF_1_3, // below minimum 1.4 for PDF/A-1
        );

        $result = (new PdfAValidator())->validate($doc, PdfAConformance::PdfA1b);

        self::assertHasWarning('pdf-version', $result);
    }

    #[Test]
    public function validateEmitsVersionWarnForPdf14WithPdfA2b(): void
    {
        $doc = self::makeDoc(
            [1 => '<</Type /Catalog /Pages 2 0 R>>', 2 => '<</Type /Pages /Kids [] /Count 0>>'],
            self::withId(['Root' => new PdfIndirectReference(1, 0)]),
            PdfVersion::PDF_1_4, // below minimum 1.7 for PDF/A-2
        );

        $result = (new PdfAValidator())->validate($doc, PdfAConformance::PdfA2b);

        self::assertHasWarning('pdf-version', $result);
    }

    #[Test]
    public function validateNoVersionWarnForPdf17WithPdfA2b(): void
    {
        $doc = self::minimalCompliantDoc(PdfAConformance::PdfA2b);
        $result = (new PdfAValidator())->validate($doc, PdfAConformance::PdfA2b);

        self::assertNoError('pdf-version', $result);
    }

    // =========================================================================
    // readCatalog — exception path
    // =========================================================================

    #[Test]
    public function validateEmitsCatalogErrorWhenRootIsMissing(): void
    {
        // Trailer has no /Root → getCatalog() throws → readCatalog returns null
        $doc = self::makeDoc(
            [1 => '<</Type /Pages /Kids [] /Count 0>>'],
            self::withId([]), // no Root entry
        );

        $result = (new PdfAValidator())->validate($doc, PdfAConformance::PdfA2b);

        self::assertHasError('catalog', $result);
    }

    // =========================================================================
    // checkMetadataPresent
    // =========================================================================

    #[Test]
    public function validateEmitsMetadataPresentErrorWhenMetadataMissing(): void
    {
        $doc = self::makeDoc(
            [1 => '<</Type /Catalog /Pages 2 0 R>>', 2 => '<</Type /Pages /Kids [] /Count 0>>'],
            self::withId(['Root' => new PdfIndirectReference(1, 0)]),
        );

        $result = (new PdfAValidator())->validate($doc, PdfAConformance::PdfA2b);

        self::assertHasError('metadata.present', $result);
    }

    #[Test]
    public function validateEmitsMetadataTypeErrorWhenMetadataIsNotStream(): void
    {
        // /Metadata points to a plain dict, not a stream
        $doc = self::makeDoc(
            [
                1 => '<</Type /Catalog /Pages 2 0 R /Metadata 3 0 R>>',
                2 => '<</Type /Pages /Kids [] /Count 0>>',
                3 => '<</NotAStream /True>>', // dict, not stream
            ],
            self::withId(['Root' => new PdfIndirectReference(1, 0)]),
        );

        $result = (new PdfAValidator())->validate($doc, PdfAConformance::PdfA2b);

        self::assertHasError('metadata.type', $result);
    }

    #[Test]
    public function validateEmitsMetadataUnfilteredErrorWhenMetadataStreamHasFilter(): void
    {
        // The PdfObjectParser strips /Filter from the stream dict after decoding, so we cannot
        // test this path by embedding a raw FlateDecode stream — it would throw on fake data.
        // Instead we inject a pre-built PdfStream (with /Filter still present in its dict)
        // directly into the document's object cache, bypassing the parser entirely.
        $doc = self::makeDoc(
            [
                1 => '<</Type /Catalog /Pages 2 0 R /Metadata 3 0 R>>',
                2 => '<</Type /Pages /Kids [] /Count 0>>',
                // Object 3 intentionally absent from content; injected via cache below.
            ],
            self::withId(['Root' => new PdfIndirectReference(1, 0)]),
        );

        $streamWithFilter = new PdfStream(
            new PdfDictionary(['Filter' => new PdfName('FlateDecode')]),
            new PdfRawStreamData('fake-compressed-data'),
        );
        $rc = new ReflectionClass(PdfReadDocument::class);
        $rc->getProperty('cache')->setValue($doc, [3 => $streamWithFilter]);

        $result = (new PdfAValidator())->validate($doc, PdfAConformance::PdfA2b);

        self::assertHasError('metadata.unfiltered', $result);
    }

    // =========================================================================
    // checkXmpMetadata
    // =========================================================================

    #[Test]
    public function validateEmitsXmpPartErrorWhenPdfaidPartIsWrong(): void
    {
        // Build with PDF/A-1b XMP (part=1) but validate as PDF/A-2b (expects part=2)
        $xmp = self::xmp(PdfAConformance::PdfA1b);
        $doc = self::makeDoc(
            [
                1 => '<</Type /Catalog /Pages 2 0 R /Metadata 3 0 R>>',
                2 => '<</Type /Pages /Kids [] /Count 0>>',
                3 => "<<>>\nstream\n{$xmp}\nendstream",
            ],
            self::withId(['Root' => new PdfIndirectReference(1, 0)]),
        );

        $result = (new PdfAValidator())->validate($doc, PdfAConformance::PdfA2b);

        self::assertHasError('xmp.pdfaid.part', $result);
    }

    #[Test]
    public function validateEmitsXmpConformanceErrorWhenPdfaidConformanceIsWrong(): void
    {
        // Build with PDF/A-1b XMP (conformance=B) but validate as PDF/A-1a (expects A)
        $xmp = self::xmp(PdfAConformance::PdfA1b);
        $doc = self::makeDoc(
            [
                1 => '<</Type /Catalog /Pages 2 0 R /Metadata 3 0 R>>',
                2 => '<</Type /Pages /Kids [] /Count 0>>',
                3 => "<<>>\nstream\n{$xmp}\nendstream",
            ],
            self::withId(['Root' => new PdfIndirectReference(1, 0)]),
            PdfVersion::PDF_1_4,
        );

        $result = (new PdfAValidator())->validate($doc, PdfAConformance::PdfA1a);

        self::assertHasError('xmp.pdfaid.conformance', $result);
    }

    #[Test]
    public function validateEmitsXmpPartErrorWhenPdfaidPartElementMissing(): void
    {
        // XMP with no pdfaid:part element at all
        $xmp = <<<'XMP'
<?xpacket begin="" id="W5M0MpCehiHzreSzNTczkc9d"?>
<x:xmpmeta xmlns:x="adobe:ns:meta/">
  <rdf:RDF xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#">
    <rdf:Description xmlns:dc="http://purl.org/dc/elements/1.1/">
      <dc:format>application/pdf</dc:format>
    </rdf:Description>
    <rdf:Description xmlns:xmp="http://ns.adobe.com/xap/1.0/">
      <xmp:CreateDate>2024-01-01T00:00:00+00:00</xmp:CreateDate>
    </rdf:Description>
  </rdf:RDF>
</x:xmpmeta>
<?xpacket end="w"?>
XMP;
        $doc = self::makeDoc(
            [
                1 => '<</Type /Catalog /Pages 2 0 R /Metadata 3 0 R>>',
                2 => '<</Type /Pages /Kids [] /Count 0>>',
                3 => "<<>>\nstream\n{$xmp}\nendstream",
            ],
            self::withId(['Root' => new PdfIndirectReference(1, 0)]),
        );

        $result = (new PdfAValidator())->validate($doc, PdfAConformance::PdfA2b);

        self::assertHasError('xmp.pdfaid.part', $result);
    }

    #[Test]
    public function validateEmitsXmpConformanceErrorWhenPdfaidConformanceElementMissing(): void
    {
        // XMP has correct pdfaid:part but no pdfaid:conformance element
        $xmp = <<<'XMP'
<?xpacket begin="" id="W5M0MpCehiHzreSzNTczkc9d"?>
<x:xmpmeta xmlns:x="adobe:ns:meta/">
  <rdf:RDF xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#">
    <rdf:Description xmlns:pdfaid="http://www.aiim.org/pdfa/ns/id/">
      <pdfaid:part>2</pdfaid:part>
    </rdf:Description>
    <rdf:Description xmlns:dc="http://purl.org/dc/elements/1.1/">
      <dc:format>application/pdf</dc:format>
    </rdf:Description>
    <rdf:Description xmlns:xmp="http://ns.adobe.com/xap/1.0/">
      <xmp:CreateDate>2024-01-01T00:00:00+00:00</xmp:CreateDate>
    </rdf:Description>
  </rdf:RDF>
</x:xmpmeta>
<?xpacket end="w"?>
XMP;
        $doc = self::makeDoc(
            [
                1 => '<</Type /Catalog /Pages 2 0 R /Metadata 3 0 R>>',
                2 => '<</Type /Pages /Kids [] /Count 0>>',
                3 => "<<>>\nstream\n{$xmp}\nendstream",
            ],
            self::withId(['Root' => new PdfIndirectReference(1, 0)]),
        );

        $result = (new PdfAValidator())->validate($doc, PdfAConformance::PdfA2b);

        self::assertHasError('xmp.pdfaid.conformance', $result);
    }

    #[Test]
    public function validateEmitsXmpDcFormatWarnWhenApplicationPdfMissing(): void
    {
        // XMP correct part/conformance but no "application/pdf" and missing namespaces
        $xmp = <<<'XMP'
<?xpacket begin="" id="W5M0MpCehiHzreSzNTczkc9d"?>
<x:xmpmeta xmlns:x="adobe:ns:meta/">
  <rdf:RDF xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#">
    <rdf:Description xmlns:pdfaid="http://www.aiim.org/pdfa/ns/id/">
      <pdfaid:part>2</pdfaid:part>
      <pdfaid:conformance>B</pdfaid:conformance>
    </rdf:Description>
  </rdf:RDF>
</x:xmpmeta>
<?xpacket end="w"?>
XMP;
        $doc = self::makeDoc(
            [
                1 => '<</Type /Catalog /Pages 2 0 R /Metadata 3 0 R>>',
                2 => '<</Type /Pages /Kids [] /Count 0>>',
                3 => "<<>>\nstream\n{$xmp}\nendstream",
            ],
            self::withId(['Root' => new PdfIndirectReference(1, 0)]),
        );

        $result = (new PdfAValidator())->validate($doc, PdfAConformance::PdfA2b);

        self::assertHasWarning('xmp.dc.format', $result);
        self::assertHasWarning('xmp.ns.dc', $result);
        self::assertHasWarning('xmp.ns.xmp', $result);
    }

    // =========================================================================
    // checkOutputIntents
    // =========================================================================

    #[Test]
    public function validateEmitsOutputIntentsWarnWhenMissing(): void
    {
        $doc = self::minimalCompliantDoc(PdfAConformance::PdfA2b);
        $result = (new PdfAValidator())->validate($doc, PdfAConformance::PdfA2b);

        self::assertHasWarning('output-intents', $result);
    }

    #[Test]
    public function validateNoOutputIntentsWarnWhenPresent(): void
    {
        $xmpData = self::xmp(PdfAConformance::PdfA2b);
        $doc = self::makeDoc(
            [
                1 => '<</Type /Catalog /Pages 2 0 R /Metadata 3 0 R /OutputIntents 4 0 R>>',
                2 => '<</Type /Pages /Kids [] /Count 0>>',
                3 => "<<>>\nstream\n{$xmpData}\nendstream",
                4 => '[<</Type /OutputIntent /S /GTS_PDFA1>>]',
            ],
            self::withId(['Root' => new PdfIndirectReference(1, 0)]),
        );

        $result = (new PdfAValidator())->validate($doc, PdfAConformance::PdfA2b);

        $warnRules = array_map(static fn($w) => $w->rule, $result->getWarnings());
        self::assertNotContains('output-intents', $warnRules);
    }

    // =========================================================================
    // checkForbiddenActions
    // =========================================================================

    #[Test]
    public function validateEmitsForbiddenActionErrorWhenOpenActionIsJavaScript(): void
    {
        $xmpData = self::xmp(PdfAConformance::PdfA2b);
        $doc = self::makeDoc(
            [
                1 => '<</Type /Catalog /Pages 2 0 R /Metadata 3 0 R /OpenAction 4 0 R>>',
                2 => '<</Type /Pages /Kids [] /Count 0>>',
                3 => "<<>>\nstream\n{$xmpData}\nendstream",
                4 => '<</Type /Action /S /JavaScript /JS (alert(1))>>',
            ],
            self::withId(['Root' => new PdfIndirectReference(1, 0)]),
        );

        $result = (new PdfAValidator())->validate($doc, PdfAConformance::PdfA2b);

        self::assertHasError('forbidden-action', $result);
    }

    #[Test]
    public function validateNoForbiddenActionErrorWhenOpenActionIsAllowed(): void
    {
        $xmpData = self::xmp(PdfAConformance::PdfA2b);
        $doc = self::makeDoc(
            [
                1 => '<</Type /Catalog /Pages 2 0 R /Metadata 3 0 R /OpenAction 4 0 R>>',
                2 => '<</Type /Pages /Kids [] /Count 0>>',
                3 => "<<>>\nstream\n{$xmpData}\nendstream",
                4 => '<</Type /Action /S /GoTo /D [2 0 R /Fit]>>',
            ],
            self::withId(['Root' => new PdfIndirectReference(1, 0)]),
        );

        $result = (new PdfAValidator())->validate($doc, PdfAConformance::PdfA2b);

        self::assertNoError('forbidden-action', $result);
    }

    #[Test]
    public function validateNoForbiddenActionErrorWhenOpenActionSIsNotName(): void
    {
        // /S value is a string, not a name — checkActionType returns early
        $xmpData = self::xmp(PdfAConformance::PdfA2b);
        $doc = self::makeDoc(
            [
                1 => '<</Type /Catalog /Pages 2 0 R /Metadata 3 0 R /OpenAction 4 0 R>>',
                2 => '<</Type /Pages /Kids [] /Count 0>>',
                3 => "<<>>\nstream\n{$xmpData}\nendstream",
                4 => '<</Type /Action /S (JavaScript)>>', // string, not name
            ],
            self::withId(['Root' => new PdfIndirectReference(1, 0)]),
        );

        $result = (new PdfAValidator())->validate($doc, PdfAConformance::PdfA2b);

        self::assertNoError('forbidden-action', $result);
    }

    #[Test]
    public function validateChecksForbiddenActionsInAaDictionary(): void
    {
        // /AA with a JavaScript trigger → forbidden action in AA
        $xmpData = self::xmp(PdfAConformance::PdfA2b);
        $doc = self::makeDoc(
            [
                1 => '<</Type /Catalog /Pages 2 0 R /Metadata 3 0 R /AA 4 0 R>>',
                2 => '<</Type /Pages /Kids [] /Count 0>>',
                3 => "<<>>\nstream\n{$xmpData}\nendstream",
                4 => '<</WC 5 0 R>>',
                5 => '<</S /JavaScript /JS (x)>>',
            ],
            self::withId(['Root' => new PdfIndirectReference(1, 0)]),
        );

        $result = (new PdfAValidator())->validate($doc, PdfAConformance::PdfA2b);

        self::assertHasError('forbidden-action', $result);
    }

    #[Test]
    public function validateHandlesAaWhereResolvedIsNotDict(): void
    {
        // /AA ref points to a non-dict object (integer) → early return
        $xmpData = self::xmp(PdfAConformance::PdfA2b);
        $doc = self::makeDoc(
            [
                1 => '<</Type /Catalog /Pages 2 0 R /Metadata 3 0 R /AA 4 0 R>>',
                2 => '<</Type /Pages /Kids [] /Count 0>>',
                3 => "<<>>\nstream\n{$xmpData}\nendstream",
                4 => '42', // integer, not dict
            ],
            self::withId(['Root' => new PdfIndirectReference(1, 0)]),
        );

        $result = (new PdfAValidator())->validate($doc, PdfAConformance::PdfA2b);

        self::assertInstanceOf(PdfAValidationResult::class, $result);
    }

    #[Test]
    public function validateSkipsAaTriggerWhereResolvedIsNotDict(): void
    {
        // /AA dict has a trigger whose ref resolves to a non-dict → continue
        $xmpData = self::xmp(PdfAConformance::PdfA2b);
        $doc = self::makeDoc(
            [
                1 => '<</Type /Catalog /Pages 2 0 R /Metadata 3 0 R /AA 4 0 R>>',
                2 => '<</Type /Pages /Kids [] /Count 0>>',
                3 => "<<>>\nstream\n{$xmpData}\nendstream",
                4 => '<</WC 5 0 R>>',
                5 => '99', // integer, not dict
            ],
            self::withId(['Root' => new PdfIndirectReference(1, 0)]),
        );

        $result = (new PdfAValidator())->validate($doc, PdfAConformance::PdfA2b);

        self::assertNoError('forbidden-action', $result);
    }

    // =========================================================================
    // checkAcroFormFonts — defensive null/type checks
    // =========================================================================

    #[Test]
    public function validateHandlesAcroFormWithoutDr(): void
    {
        // AcroForm present but no /DR → early return, no crash
        $xmpData = self::xmp(PdfAConformance::PdfA2b);
        $doc = self::makeDoc(
            [
                1 => '<</Type /Catalog /Pages 2 0 R /Metadata 3 0 R /AcroForm 4 0 R>>',
                2 => '<</Type /Pages /Kids [] /Count 0>>',
                3 => "<<>>\nstream\n{$xmpData}\nendstream",
                4 => '<</Fields []>>', // AcroForm dict, no DR
            ],
            self::withId(['Root' => new PdfIndirectReference(1, 0)]),
        );

        $result = (new PdfAValidator())->validate($doc, PdfAConformance::PdfA2b);

        self::assertInstanceOf(PdfAValidationResult::class, $result);
    }

    #[Test]
    public function validateHandlesAcroFormWhereResolvedIsNotDict(): void
    {
        // /AcroForm ref points to a non-dict object (integer) → early return
        $xmpData = self::xmp(PdfAConformance::PdfA2b);
        $doc = self::makeDoc(
            [
                1 => '<</Type /Catalog /Pages 2 0 R /Metadata 3 0 R /AcroForm 4 0 R>>',
                2 => '<</Type /Pages /Kids [] /Count 0>>',
                3 => "<<>>\nstream\n{$xmpData}\nendstream",
                4 => '42', // integer, not dict
            ],
            self::withId(['Root' => new PdfIndirectReference(1, 0)]),
        );

        $result = (new PdfAValidator())->validate($doc, PdfAConformance::PdfA2b);

        self::assertInstanceOf(PdfAValidationResult::class, $result);
    }

    #[Test]
    public function validateHandlesAcroFormDrWhereResolvedIsNotDict(): void
    {
        // AcroForm has /DR but DR resolves to non-dict
        $xmpData = self::xmp(PdfAConformance::PdfA2b);
        $doc = self::makeDoc(
            [
                1 => '<</Type /Catalog /Pages 2 0 R /Metadata 3 0 R /AcroForm 4 0 R>>',
                2 => '<</Type /Pages /Kids [] /Count 0>>',
                3 => "<<>>\nstream\n{$xmpData}\nendstream",
                4 => '<</DR 5 0 R>>',
                5 => 'true', // boolean, not dict
            ],
            self::withId(['Root' => new PdfIndirectReference(1, 0)]),
        );

        $result = (new PdfAValidator())->validate($doc, PdfAConformance::PdfA2b);

        self::assertInstanceOf(PdfAValidationResult::class, $result);
    }

    #[Test]
    public function validateHandlesAcroFormDrWithNoFont(): void
    {
        // AcroForm has /DR dict but no /Font entry
        $xmpData = self::xmp(PdfAConformance::PdfA2b);
        $doc = self::makeDoc(
            [
                1 => '<</Type /Catalog /Pages 2 0 R /Metadata 3 0 R /AcroForm 4 0 R>>',
                2 => '<</Type /Pages /Kids [] /Count 0>>',
                3 => "<<>>\nstream\n{$xmpData}\nendstream",
                4 => '<</DR 5 0 R>>',
                5 => '<</ProcSet [/PDF]>>', // DR dict, no Font
            ],
            self::withId(['Root' => new PdfIndirectReference(1, 0)]),
        );

        $result = (new PdfAValidator())->validate($doc, PdfAConformance::PdfA2b);

        self::assertInstanceOf(PdfAValidationResult::class, $result);
    }

    #[Test]
    public function validateHandlesAcroFormFontDictWhereResolvedIsNotDict(): void
    {
        // /Font in DR resolves to non-dict → early return
        $xmpData = self::xmp(PdfAConformance::PdfA2b);
        $doc = self::makeDoc(
            [
                1 => '<</Type /Catalog /Pages 2 0 R /Metadata 3 0 R /AcroForm 4 0 R>>',
                2 => '<</Type /Pages /Keys [] /Count 0>>',
                3 => "<<>>\nstream\n{$xmpData}\nendstream",
                4 => '<</DR 5 0 R>>',
                5 => '<</Font 6 0 R>>',
                6 => '99', // integer, not dict
            ],
            self::withId(['Root' => new PdfIndirectReference(1, 0)]),
        );

        $result = (new PdfAValidator())->validate($doc, PdfAConformance::PdfA2b);

        self::assertInstanceOf(PdfAValidationResult::class, $result);
    }

    #[Test]
    public function validateSkipsAcroFormDrFontEntryWhereResolvedIsNotDict(): void
    {
        // AcroForm DR /Font dict has an entry whose ref resolves to non-dict → continue
        $xmpData = self::xmp(PdfAConformance::PdfA2b);
        $doc = self::makeDoc(
            [
                1 => '<</Type /Catalog /Pages 2 0 R /Metadata 3 0 R /AcroForm 4 0 R>>',
                2 => '<</Type /Pages /Kids [] /Count 0>>',
                3 => "<<>>\nstream\n{$xmpData}\nendstream",
                4 => '<</DR 5 0 R /Fields []>>',
                5 => '<</Font 6 0 R>>',
                6 => '<</Helv 7 0 R>>',
                7 => '99', // integer, not dict
            ],
            self::withId(['Root' => new PdfIndirectReference(1, 0)]),
        );

        $result = (new PdfAValidator())->validate($doc, PdfAConformance::PdfA2b);

        self::assertInstanceOf(PdfAValidationResult::class, $result);
    }

    #[Test]
    public function validateChecksAcroFormDrFontsForEmbedding(): void
    {
        // AcroForm DR has Helvetica (standard Type1, not embedded) → error
        $xmpData = self::xmp(PdfAConformance::PdfA2b);
        $doc = self::makeDoc(
            [
                1 => '<</Type /Catalog /Pages 2 0 R /Metadata 3 0 R /AcroForm 4 0 R>>',
                2 => '<</Type /Pages /Kids [] /Count 0>>',
                3 => "<<>>\nstream\n{$xmpData}\nendstream",
                4 => '<</DR 5 0 R /Fields []>>',
                5 => '<</Font 6 0 R>>',
                6 => '<</Helv 7 0 R>>',
                7 => '<</Type /Font /Subtype /Type1 /BaseFont /Helvetica>>',
            ],
            self::withId(['Root' => new PdfIndirectReference(1, 0)]),
        );

        $result = (new PdfAValidator())->validate($doc, PdfAConformance::PdfA2b);

        self::assertHasError('font.not-embedded', $result);
    }

    // =========================================================================
    // checkPageMediaBox
    // =========================================================================

    #[Test]
    public function validateEmitsMediaBoxWarnWhenPageHasNoDirectMediaBox(): void
    {
        $xmpData = self::xmp(PdfAConformance::PdfA2b);
        $doc = self::makeDoc(
            [
                1 => '<</Type /Catalog /Pages 2 0 R /Metadata 3 0 R>>',
                2 => '<</Type /Pages /Kids [4 0 R] /Count 1>>',
                3 => "<<>>\nstream\n{$xmpData}\nendstream",
                4 => '<</Type /Page /Parent 2 0 R /Resources <<>>>>', // no MediaBox
            ],
            self::withId(['Root' => new PdfIndirectReference(1, 0)]),
        );

        $result = (new PdfAValidator())->validate($doc, PdfAConformance::PdfA2b);

        self::assertHasWarning('page.mediabox', $result);
    }

    // =========================================================================
    // checkFont — missing /Subtype and /BaseFont
    // =========================================================================

    #[Test]
    public function validateHandlesFontWithoutSubtypeOrBaseFont(): void
    {
        // Font dict has neither /Subtype nor /BaseFont → falls through to default
        // match arm (subtypeStr === null) and falls back to $localName for the name.
        $xmpData = self::xmp(PdfAConformance::PdfA2b);
        $doc = self::makeDoc(
            [
                1 => '<</Type /Catalog /Pages 2 0 R /Metadata 3 0 R>>',
                2 => '<</Type /Pages /Kids [4 0 R] /Count 1>>',
                3 => "<<>>\nstream\n{$xmpData}\nendstream",
                4 => '<</Type /Page /Parent 2 0 R /MediaBox [0 0 595 842]'
                    . ' /Resources <</Font <</F1 5 0 R>>>>>>',
                5 => '<</Type /Font>>', // no Subtype, no BaseFont
            ],
            self::withId(['Root' => new PdfIndirectReference(1, 0)]),
        );

        $result = (new PdfAValidator())->validate($doc, PdfAConformance::PdfA2b);

        $errorRules = array_map(static fn($e) => $e->rule, $result->getErrors());
        self::assertNotContains('font.not-embedded', $errorRules);
        self::assertNotContains('font.no-descriptor', $errorRules);
    }

    // =========================================================================
    // checkPageFonts — various font subtypes
    // =========================================================================

    #[Test]
    public function validateEmitsFontNotEmbeddedForStandardType1Font(): void
    {
        $xmpData = self::xmp(PdfAConformance::PdfA2b);
        $doc = self::makeDoc(
            [
                1 => '<</Type /Catalog /Pages 2 0 R /Metadata 3 0 R>>',
                2 => '<</Type /Pages /Kids [4 0 R] /Count 1>>',
                3 => "<<>>\nstream\n{$xmpData}\nendstream",
                4 => '<</Type /Page /Parent 2 0 R /MediaBox [0 0 595 842]'
                    . ' /Resources <</Font <</F1 5 0 R>>>>>>',
                5 => '<</Type /Font /Subtype /Type1 /BaseFont /Helvetica>>',
            ],
            self::withId(['Root' => new PdfIndirectReference(1, 0)]),
        );

        $result = (new PdfAValidator())->validate($doc, PdfAConformance::PdfA2b);

        self::assertHasError('font.not-embedded', $result);
    }

    #[Test]
    public function validateEmitsFontNoDescriptorForNonStandardType1FontWithoutDescriptor(): void
    {
        // A Type1 font with a custom name (not in STANDARD_TYPE1_FONTS) and no descriptor
        $xmpData = self::xmp(PdfAConformance::PdfA2b);
        $doc = self::makeDoc(
            [
                1 => '<</Type /Catalog /Pages 2 0 R /Metadata 3 0 R>>',
                2 => '<</Type /Pages /Kids [4 0 R] /Count 1>>',
                3 => "<<>>\nstream\n{$xmpData}\nendstream",
                4 => '<</Type /Page /Parent 2 0 R /MediaBox [0 0 595 842]'
                    . ' /Resources <</Font <</F1 5 0 R>>>>>>',
                5 => '<</Type /Font /Subtype /Type1 /BaseFont /CustomFont-Regular>>',
            ],
            self::withId(['Root' => new PdfIndirectReference(1, 0)]),
        );

        $result = (new PdfAValidator())->validate($doc, PdfAConformance::PdfA2b);

        self::assertHasError('font.no-descriptor', $result);
    }

    #[Test]
    public function validateSkipsPageFontEntryWhereResolvedIsNotDict(): void
    {
        // Page Resources /Font dict has an entry whose ref resolves to non-dict → continue
        $xmpData = self::xmp(PdfAConformance::PdfA2b);
        $doc = self::makeDoc(
            [
                1 => '<</Type /Catalog /Pages 2 0 R /Metadata 3 0 R>>',
                2 => '<</Type /Pages /Kids [4 0 R] /Count 1>>',
                3 => "<<>>\nstream\n{$xmpData}\nendstream",
                4 => '<</Type /Page /Parent 2 0 R /MediaBox [0 0 595 842]'
                    . ' /Resources <</Font <</F1 5 0 R>>>>>>',
                5 => '99', // integer, not dict
            ],
            self::withId(['Root' => new PdfIndirectReference(1, 0)]),
        );

        $result = (new PdfAValidator())->validate($doc, PdfAConformance::PdfA2b);

        self::assertInstanceOf(PdfAValidationResult::class, $result);
    }

    #[Test]
    public function validateNoFontNotEmbeddedForType1FontWithEmbeddedFontFile(): void
    {
        // Type1 font has /FontDescriptor with /FontFile present → no error
        $xmpData = self::xmp(PdfAConformance::PdfA2b);
        $doc = self::makeDoc(
            [
                1 => '<</Type /Catalog /Pages 2 0 R /Metadata 3 0 R>>',
                2 => '<</Type /Pages /Kids [4 0 R] /Count 1>>',
                3 => "<<>>\nstream\n{$xmpData}\nendstream",
                4 => '<</Type /Page /Parent 2 0 R /MediaBox [0 0 595 842]'
                    . ' /Resources <</Font <</F1 5 0 R>>>>>>',
                5 => '<</Type /Font /Subtype /Type1 /BaseFont /MyFont /FontDescriptor 6 0 R>>',
                6 => '<</Type /FontDescriptor /FontName /MyFont /Flags 32 /FontFile 7 0 R>>',
                7 => "<<>>\nstream\nfakefontprogram\nendstream",
            ],
            self::withId(['Root' => new PdfIndirectReference(1, 0)]),
        );

        $result = (new PdfAValidator())->validate($doc, PdfAConformance::PdfA2b);

        self::assertNoError('font.not-embedded', $result);
    }

    #[Test]
    public function validateEmitsFontNotEmbeddedForType1WithDescriptorButNoFontFile(): void
    {
        // Type1 font has /FontDescriptor but no FontFile/FontFile2/FontFile3
        $xmpData = self::xmp(PdfAConformance::PdfA2b);
        $doc = self::makeDoc(
            [
                1 => '<</Type /Catalog /Pages 2 0 R /Metadata 3 0 R>>',
                2 => '<</Type /Pages /Kids [4 0 R] /Count 1>>',
                3 => "<<>>\nstream\n{$xmpData}\nendstream",
                4 => '<</Type /Page /Parent 2 0 R /MediaBox [0 0 595 842]'
                    . ' /Resources <</Font <</F1 5 0 R>>>>>>',
                5 => '<</Type /Font /Subtype /Type1 /BaseFont /MyFont /FontDescriptor 6 0 R>>',
                6 => '<</Type /FontDescriptor /FontName /MyFont /Flags 32>>', // no FontFile entries
            ],
            self::withId(['Root' => new PdfIndirectReference(1, 0)]),
        );

        $result = (new PdfAValidator())->validate($doc, PdfAConformance::PdfA2b);

        self::assertHasError('font.not-embedded', $result);
    }

    #[Test]
    public function validateSkipsType1FontWhenFontDescriptorResolvesToNonDict(): void
    {
        // FontDescriptor indirect ref points to a non-dict → early return in checkType1Font
        $xmpData = self::xmp(PdfAConformance::PdfA2b);
        $doc = self::makeDoc(
            [
                1 => '<</Type /Catalog /Pages 2 0 R /Metadata 3 0 R>>',
                2 => '<</Type /Pages /Kids [4 0 R] /Count 1>>',
                3 => "<<>>\nstream\n{$xmpData}\nendstream",
                4 => '<</Type /Page /Parent 2 0 R /MediaBox [0 0 595 842]'
                    . ' /Resources <</Font <</F1 5 0 R>>>>>>',
                5 => '<</Type /Font /Subtype /Type1 /BaseFont /MyFont /FontDescriptor 6 0 R>>',
                6 => '42', // integer, not a dict
            ],
            self::withId(['Root' => new PdfIndirectReference(1, 0)]),
        );

        $result = (new PdfAValidator())->validate($doc, PdfAConformance::PdfA2b);

        self::assertInstanceOf(PdfAValidationResult::class, $result);
    }

    #[Test]
    public function validateEmitsFontNoDescriptorForTrueTypeFontWithoutDescriptor(): void
    {
        $xmpData = self::xmp(PdfAConformance::PdfA2b);
        $doc = self::makeDoc(
            [
                1 => '<</Type /Catalog /Pages 2 0 R /Metadata 3 0 R>>',
                2 => '<</Type /Pages /Kids [4 0 R] /Count 1>>',
                3 => "<<>>\nstream\n{$xmpData}\nendstream",
                4 => '<</Type /Page /Parent 2 0 R /MediaBox [0 0 595 842]'
                    . ' /Resources <</Font <</F1 5 0 R>>>>>>',
                5 => '<</Type /Font /Subtype /TrueType /BaseFont /MyTTF>>', // no descriptor
            ],
            self::withId(['Root' => new PdfIndirectReference(1, 0)]),
        );

        $result = (new PdfAValidator())->validate($doc, PdfAConformance::PdfA2b);

        self::assertHasError('font.no-descriptor', $result);
    }

    #[Test]
    public function validateEmitsFontNotEmbeddedForTrueTypeWithDescriptorButNoFontFile(): void
    {
        $xmpData = self::xmp(PdfAConformance::PdfA2b);
        $doc = self::makeDoc(
            [
                1 => '<</Type /Catalog /Pages 2 0 R /Metadata 3 0 R>>',
                2 => '<</Type /Pages /Kids [4 0 R] /Count 1>>',
                3 => "<<>>\nstream\n{$xmpData}\nendstream",
                4 => '<</Type /Page /Parent 2 0 R /MediaBox [0 0 595 842]'
                    . ' /Resources <</Font <</F1 5 0 R>>>>>>',
                5 => '<</Type /Font /Subtype /TrueType /BaseFont /MyTTF /FontDescriptor 6 0 R>>',
                6 => '<</Type /FontDescriptor /FontName /MyTTF /Flags 32>>', // no FontFile2
            ],
            self::withId(['Root' => new PdfIndirectReference(1, 0)]),
        );

        $result = (new PdfAValidator())->validate($doc, PdfAConformance::PdfA2b);

        self::assertHasError('font.not-embedded', $result);
    }

    #[Test]
    public function validateNoFontNotEmbeddedForTrueTypeFontWithEmbeddedFontFile(): void
    {
        // TrueType font has /FontDescriptor with /FontFile2 present → no error
        $xmpData = self::xmp(PdfAConformance::PdfA2b);
        $doc = self::makeDoc(
            [
                1 => '<</Type /Catalog /Pages 2 0 R /Metadata 3 0 R>>',
                2 => '<</Type /Pages /Kids [4 0 R] /Count 1>>',
                3 => "<<>>\nstream\n{$xmpData}\nendstream",
                4 => '<</Type /Page /Parent 2 0 R /MediaBox [0 0 595 842]'
                    . ' /Resources <</Font <</F1 5 0 R>>>>>>',
                5 => '<</Type /Font /Subtype /TrueType /BaseFont /MyTTF /FontDescriptor 6 0 R>>',
                6 => '<</Type /FontDescriptor /FontName /MyTTF /Flags 32 /FontFile2 7 0 R>>',
                7 => "<<>>\nstream\nfakefontprogram\nendstream",
            ],
            self::withId(['Root' => new PdfIndirectReference(1, 0)]),
        );

        $result = (new PdfAValidator())->validate($doc, PdfAConformance::PdfA2b);

        self::assertNoError('font.not-embedded', $result);
    }

    #[Test]
    public function validateSkipsTrueTypeFontWhenFontDescriptorResolvesToNonDict(): void
    {
        // FontDescriptor indirect ref points to a non-dict → early return in checkSimpleFont
        $xmpData = self::xmp(PdfAConformance::PdfA2b);
        $doc = self::makeDoc(
            [
                1 => '<</Type /Catalog /Pages 2 0 R /Metadata 3 0 R>>',
                2 => '<</Type /Pages /Kids [4 0 R] /Count 1>>',
                3 => "<<>>\nstream\n{$xmpData}\nendstream",
                4 => '<</Type /Page /Parent 2 0 R /MediaBox [0 0 595 842]'
                    . ' /Resources <</Font <</F1 5 0 R>>>>>>',
                5 => '<</Type /Font /Subtype /TrueType /BaseFont /MyTTF /FontDescriptor 6 0 R>>',
                6 => '99', // integer, not a dict
            ],
            self::withId(['Root' => new PdfIndirectReference(1, 0)]),
        );

        $result = (new PdfAValidator())->validate($doc, PdfAConformance::PdfA2b);

        self::assertInstanceOf(PdfAValidationResult::class, $result);
    }

    #[Test]
    public function validateEmitsToUnicodeWarnForType0FontWithoutToUnicode(): void
    {
        // Type0 composite font with no /ToUnicode → warning
        $xmpData = self::xmp(PdfAConformance::PdfA2b);
        $doc = self::makeDoc(
            [
                1 => '<</Type /Catalog /Pages 2 0 R /Metadata 3 0 R>>',
                2 => '<</Type /Pages /Kids [4 0 R] /Count 1>>',
                3 => "<<>>\nstream\n{$xmpData}\nendstream",
                4 => '<</Type /Page /Parent 2 0 R /MediaBox [0 0 595 842]'
                    . ' /Resources <</Font <</F1 5 0 R>>>>>>',
                5 => '<</Type /Font /Subtype /Type0 /BaseFont /MyType0 /DescendantFonts [6 0 R]>>',
                // no ToUnicode
                6 => '<</Type /Font /Subtype /CIDFontType2 /BaseFont /MyType0'
                    . ' /CIDSystemInfo <</Registry (Adobe) /Ordering (Identity) /Supplement 0>>'
                    . ' /FontDescriptor 7 0 R>>',
                7 => '<</Type /FontDescriptor /FontName /MyType0 /Flags 32 /FontFile2 8 0 R>>',
                8 => "<<>>\nstream\nfakefontdata\nendstream",
            ],
            self::withId(['Root' => new PdfIndirectReference(1, 0)]),
        );

        $result = (new PdfAValidator())->validate($doc, PdfAConformance::PdfA2b);

        self::assertHasWarning('font.no-tounicode', $result);
    }

    #[Test]
    public function validateNoToUnicodeWarnWhenType0HasToUnicode(): void
    {
        // Type0 with /ToUnicode → no warning
        $xmpData = self::xmp(PdfAConformance::PdfA2b);
        $tounicode = "/CIDInit /ProcSet findresource begin 12 dict begin begincmap endcmap end end";
        $doc = self::makeDoc(
            [
                1 => '<</Type /Catalog /Pages 2 0 R /Metadata 3 0 R>>',
                2 => '<</Type /Pages /Kids [4 0 R] /Count 1>>',
                3 => "<<>>\nstream\n{$xmpData}\nendstream",
                4 => '<</Type /Page /Parent 2 0 R /MediaBox [0 0 595 842]'
                    . ' /Resources <</Font <</F1 5 0 R>>>>>>',
                5 => '<</Type /Font /Subtype /Type0 /BaseFont /MyFont /ToUnicode 9 0 R'
                    . ' /DescendantFonts [6 0 R]>>',
                6 => '<</Type /Font /Subtype /CIDFontType2 /BaseFont /MyFont'
                    . ' /CIDSystemInfo <</Registry (Adobe) /Ordering (Identity) /Supplement 0>>'
                    . ' /FontDescriptor 7 0 R>>',
                7 => '<</Type /FontDescriptor /FontName /MyFont /Flags 32 /FontFile2 8 0 R>>',
                8 => "<<>>\nstream\nfakefontdata\nendstream",
                9 => "<<>>\nstream\n{$tounicode}\nendstream",
            ],
            self::withId(['Root' => new PdfIndirectReference(1, 0)]),
        );

        $result = (new PdfAValidator())->validate($doc, PdfAConformance::PdfA2b);

        foreach ($result->getWarnings() as $w) {
            self::assertNotSame('font.no-tounicode', $w->rule);
        }
    }

    #[Test]
    public function validateSkipsDescendantFontWhereResolvedIsNotDict(): void
    {
        // DescendantFonts array has an entry whose ref resolves to non-dict → continue
        $xmpData = self::xmp(PdfAConformance::PdfA2b);
        $doc = self::makeDoc(
            [
                1 => '<</Type /Catalog /Pages 2 0 R /Metadata 3 0 R>>',
                2 => '<</Type /Pages /Kids [4 0 R] /Count 1>>',
                3 => "<<>>\nstream\n{$xmpData}\nendstream",
                4 => '<</Type /Page /Parent 2 0 R /MediaBox [0 0 595 842]'
                    . ' /Resources <</Font <</F1 5 0 R>>>>>>',
                5 => '<</Type /Font /Subtype /Type0 /BaseFont /F /ToUnicode 7 0 R'
                    . ' /DescendantFonts [6 0 R]>>',
                6 => '99', // integer, not dict
                7 => "<<>>\nstream\ncmap\nendstream",
            ],
            self::withId(['Root' => new PdfIndirectReference(1, 0)]),
        );

        $result = (new PdfAValidator())->validate($doc, PdfAConformance::PdfA2b);

        self::assertInstanceOf(PdfAValidationResult::class, $result);
    }

    #[Test]
    public function validateHandlesType0FontWhereDescendantFontsIsNotArray(): void
    {
        // DescendantFonts is present but resolves to non-array
        $xmpData = self::xmp(PdfAConformance::PdfA2b);
        $doc = self::makeDoc(
            [
                1 => '<</Type /Catalog /Pages 2 0 R /Metadata 3 0 R>>',
                2 => '<</Type /Pages /Kids [4 0 R] /Count 1>>',
                3 => "<<>>\nstream\n{$xmpData}\nendstream",
                4 => '<</Type /Page /Parent 2 0 R /MediaBox [0 0 595 842]'
                    . ' /Resources <</Font <</F1 5 0 R>>>>>>',
                5 => '<</Type /Font /Subtype /Type0 /BaseFont /F /DescendantFonts 6 0 R'
                    . ' /ToUnicode 7 0 R>>',
                6 => '42', // integer, not array
                7 => "<<>>\nstream\ncmap\nendstream",
            ],
            self::withId(['Root' => new PdfIndirectReference(1, 0)]),
        );

        $result = (new PdfAValidator())->validate($doc, PdfAConformance::PdfA2b);

        // Should not crash; may have other errors but not font.no-tounicode for this font
        self::assertInstanceOf(PdfAValidationResult::class, $result);
    }

    #[Test]
    public function validateHandlesType0FontWithNoDescendantFonts(): void
    {
        // Type0 font has no /DescendantFonts entry → early return in checkType0Font
        $xmpData = self::xmp(PdfAConformance::PdfA2b);
        $doc = self::makeDoc(
            [
                1 => '<</Type /Catalog /Pages 2 0 R /Metadata 3 0 R>>',
                2 => '<</Type /Pages /Kids [4 0 R] /Count 1>>',
                3 => "<<>>\nstream\n{$xmpData}\nendstream",
                4 => '<</Type /Page /Parent 2 0 R /MediaBox [0 0 595 842]'
                    . ' /Resources <</Font <</F1 5 0 R>>>>>>',
                5 => '<</Type /Font /Subtype /Type0 /BaseFont /F /ToUnicode 6 0 R>>',
                // No DescendantFonts entry
                6 => "<<>>\nstream\ncmap\nendstream",
            ],
            self::withId(['Root' => new PdfIndirectReference(1, 0)]),
        );

        $result = (new PdfAValidator())->validate($doc, PdfAConformance::PdfA2b);

        self::assertInstanceOf(PdfAValidationResult::class, $result);
    }

    #[Test]
    public function validateEmitsCidFontNoDescriptorError(): void
    {
        // CID descendant has no /FontDescriptor
        $xmpData = self::xmp(PdfAConformance::PdfA2b);
        $doc = self::makeDoc(
            [
                1 => '<</Type /Catalog /Pages 2 0 R /Metadata 3 0 R>>',
                2 => '<</Type /Pages /Kids [4 0 R] /Count 1>>',
                3 => "<<>>\nstream\n{$xmpData}\nendstream",
                4 => '<</Type /Page /Parent 2 0 R /MediaBox [0 0 595 842]'
                    . ' /Resources <</Font <</F1 5 0 R>>>>>>',
                5 => '<</Type /Font /Subtype /Type0 /BaseFont /F /ToUnicode 7 0 R'
                    . ' /DescendantFonts [6 0 R]>>',
                6 => '<</Type /Font /Subtype /CIDFontType2 /BaseFont /F'
                    . ' /CIDSystemInfo <</Registry (Adobe) /Ordering (Identity) /Supplement 0>>>>',
                // No FontDescriptor in object 6
                7 => "<<>>\nstream\ncmap\nendstream",
            ],
            self::withId(['Root' => new PdfIndirectReference(1, 0)]),
        );

        $result = (new PdfAValidator())->validate($doc, PdfAConformance::PdfA2b);

        self::assertHasError('font.no-descriptor', $result);
    }

    #[Test]
    public function validateEmitsCidFontNotEmbeddedWhenFontFileAbsent(): void
    {
        // CID has FontDescriptor but no font file
        $xmpData = self::xmp(PdfAConformance::PdfA2b);
        $doc = self::makeDoc(
            [
                1 => '<</Type /Catalog /Pages 2 0 R /Metadata 3 0 R>>',
                2 => '<</Type /Pages /Kids [4 0 R] /Count 1>>',
                3 => "<<>>\nstream\n{$xmpData}\nendstream",
                4 => '<</Type /Page /Parent 2 0 R /MediaBox [0 0 595 842]'
                    . ' /Resources <</Font <</F1 5 0 R>>>>>>',
                5 => '<</Type /Font /Subtype /Type0 /BaseFont /F /ToUnicode 8 0 R'
                    . ' /DescendantFonts [6 0 R]>>',
                6 => '<</Type /Font /Subtype /CIDFontType2 /BaseFont /F'
                    . ' /CIDSystemInfo <</Registry (Adobe) /Ordering (Identity) /Supplement 0>>'
                    . ' /FontDescriptor 7 0 R>>',
                7 => '<</Type /FontDescriptor /FontName /F /Flags 32>>', // no FontFile2
                8 => "<<>>\nstream\ncmap\nendstream",
            ],
            self::withId(['Root' => new PdfIndirectReference(1, 0)]),
        );

        $result = (new PdfAValidator())->validate($doc, PdfAConformance::PdfA2b);

        self::assertHasError('font.not-embedded', $result);
    }

    #[Test]
    public function validateSkipsCidFontWhenFontDescriptorResolvesToNonDict(): void
    {
        // CID FontDescriptor ref points to non-dict → early return in checkCidFontEmbedding
        $xmpData = self::xmp(PdfAConformance::PdfA2b);
        $doc = self::makeDoc(
            [
                1 => '<</Type /Catalog /Pages 2 0 R /Metadata 3 0 R>>',
                2 => '<</Type /Pages /Kids [4 0 R] /Count 1>>',
                3 => "<<>>\nstream\n{$xmpData}\nendstream",
                4 => '<</Type /Page /Parent 2 0 R /MediaBox [0 0 595 842]'
                    . ' /Resources <</Font <</F1 5 0 R>>>>>>',
                5 => '<</Type /Font /Subtype /Type0 /BaseFont /F /ToUnicode 8 0 R'
                    . ' /DescendantFonts [6 0 R]>>',
                6 => '<</Type /Font /Subtype /CIDFontType2 /BaseFont /F'
                    . ' /CIDSystemInfo <</Registry (Adobe) /Ordering (Identity) /Supplement 0>>'
                    . ' /FontDescriptor 7 0 R>>',
                7 => '55', // integer, not a dict
                8 => "<<>>\nstream\ncmap\nendstream",
            ],
            self::withId(['Root' => new PdfIndirectReference(1, 0)]),
        );

        $result = (new PdfAValidator())->validate($doc, PdfAConformance::PdfA2b);

        self::assertInstanceOf(PdfAValidationResult::class, $result);
    }

    #[Test]
    public function validateSkipsUnknownFontSubtype(): void
    {
        // Font with Subtype /Type3 — falls through match default → no error/warn from font check
        $xmpData = self::xmp(PdfAConformance::PdfA2b);
        $doc = self::makeDoc(
            [
                1 => '<</Type /Catalog /Pages 2 0 R /Metadata 3 0 R>>',
                2 => '<</Type /Pages /Kids [4 0 R] /Count 1>>',
                3 => "<<>>\nstream\n{$xmpData}\nendstream",
                4 => '<</Type /Page /Parent 2 0 R /MediaBox [0 0 595 842]'
                    . ' /Resources <</Font <</F1 5 0 R>>>>>>',
                5 => '<</Type /Font /Subtype /Type3 /BaseFont /MyType3>>',
            ],
            self::withId(['Root' => new PdfIndirectReference(1, 0)]),
        );

        $result = (new PdfAValidator())->validate($doc, PdfAConformance::PdfA2b);

        // Type3 produces neither font.not-embedded nor font.no-descriptor
        $errorRules = array_map(static fn($e) => $e->rule, $result->getErrors());
        self::assertNotContains('font.not-embedded', $errorRules);
        self::assertNotContains('font.no-descriptor', $errorRules);
    }

    #[Test]
    public function validateDeduplicatesFontObjectsAcrossPages(): void
    {
        // Same font object number referenced on two different pages: checked only once
        $xmpData = self::xmp(PdfAConformance::PdfA2b);
        $doc = self::makeDoc(
            [
                1 => '<</Type /Catalog /Pages 2 0 R /Metadata 3 0 R>>',
                2 => '<</Type /Pages /Kids [4 0 R 5 0 R] /Count 2>>',
                3 => "<<>>\nstream\n{$xmpData}\nendstream",
                4 => '<</Type /Page /Parent 2 0 R /MediaBox [0 0 595 842]'
                    . ' /Resources <</Font <</F1 6 0 R>>>>>>',
                5 => '<</Type /Page /Parent 2 0 R /MediaBox [0 0 595 842]'
                    . ' /Resources <</Font <</F1 6 0 R>>>>>>', // same font ref
                6 => '<</Type /Font /Subtype /Type1 /BaseFont /Helvetica>>',
            ],
            self::withId(['Root' => new PdfIndirectReference(1, 0)]),
        );

        $result = (new PdfAValidator())->validate($doc, PdfAConformance::PdfA2b);

        // Only one font.not-embedded error despite two pages
        $fontErrors = array_filter(
            $result->getErrors(),
            static fn($e) => $e->rule === 'font.not-embedded',
        );
        self::assertCount(1, $fontErrors);
    }

    #[Test]
    public function validateHandlesPageFontsDictWhereResolvedIsNotDict(): void
    {
        // Font entry in Resources resolves to non-dict → early return in checkPageFonts
        $xmpData = self::xmp(PdfAConformance::PdfA2b);
        $doc = self::makeDoc(
            [
                1 => '<</Type /Catalog /Pages 2 0 R /Metadata 3 0 R>>',
                2 => '<</Type /Pages /Kids [4 0 R] /Count 1>>',
                3 => "<<>>\nstream\n{$xmpData}\nendstream",
                4 => '<</Type /Page /Parent 2 0 R /MediaBox [0 0 595 842]'
                    . ' /Resources <</Font 5 0 R>>>>',
                5 => '42', // integer, not dict
            ],
            self::withId(['Root' => new PdfIndirectReference(1, 0)]),
        );

        $result = (new PdfAValidator())->validate($doc, PdfAConformance::PdfA2b);

        self::assertInstanceOf(PdfAValidationResult::class, $result);
    }

    // =========================================================================
    // checkPageTransparency (PDF/A-1b only, part === 1)
    // =========================================================================

    #[Test]
    public function validateEmitsTransparencyAlphaErrorForPdfA1bWithSubUnityFillAlpha(): void
    {
        // ExtGState with ca=0.5 (sub-unity fill opacity) → error in PDF/A-1b
        $xmpData = self::xmp(PdfAConformance::PdfA1b);
        $doc = self::makeDoc(
            [
                1 => '<</Type /Catalog /Pages 2 0 R /Metadata 3 0 R>>',
                2 => '<</Type /Pages /Kids [4 0 R] /Count 1>>',
                3 => "<<>>\nstream\n{$xmpData}\nendstream",
                4 => '<</Type /Page /Parent 2 0 R /MediaBox [0 0 595 842]'
                    . ' /Resources <</ExtGState <</GS1 5 0 R>>>>>>',
                5 => '<</Type /ExtGState /ca 0.5 /CA 1.0 /BM /Normal>>',
            ],
            self::withId(['Root' => new PdfIndirectReference(1, 0)]),
            PdfVersion::PDF_1_4,
        );

        $result = (new PdfAValidator())->validate($doc, PdfAConformance::PdfA1b);

        self::assertHasError('transparency.alpha', $result);
    }

    #[Test]
    public function validateEmitsTransparencyAlphaErrorForPdfA1bWithIntegerOpacity(): void
    {
        // /ca is an integer (e.g. 0) rather than a real → covers the PdfInteger branch
        $xmpData = self::xmp(PdfAConformance::PdfA1b);
        $doc = self::makeDoc(
            [
                1 => '<</Type /Catalog /Pages 2 0 R /Metadata 3 0 R>>',
                2 => '<</Type /Pages /Kids [4 0 R] /Count 1>>',
                3 => "<<>>\nstream\n{$xmpData}\nendstream",
                4 => '<</Type /Page /Parent 2 0 R /MediaBox [0 0 595 842]'
                    . ' /Resources <</ExtGState <</GS1 5 0 R>>>>>>',
                5 => '<</Type /ExtGState /ca 0>>', // integer 0 < 1.0 → error
            ],
            self::withId(['Root' => new PdfIndirectReference(1, 0)]),
            PdfVersion::PDF_1_4,
        );

        $result = (new PdfAValidator())->validate($doc, PdfAConformance::PdfA1b);

        self::assertHasError('transparency.alpha', $result);
    }

    #[Test]
    public function validateEmitsTransparencyAlphaErrorForPdfA1bWithSubUnityStrokeAlpha(): void
    {
        // CA < 1.0 (stroke opacity) → error
        $xmpData = self::xmp(PdfAConformance::PdfA1b);
        $doc = self::makeDoc(
            [
                1 => '<</Type /Catalog /Pages 2 0 R /Metadata 3 0 R>>',
                2 => '<</Type /Pages /Kids [4 0 R] /Count 1>>',
                3 => "<<>>\nstream\n{$xmpData}\nendstream",
                4 => '<</Type /Page /Parent 2 0 R /MediaBox [0 0 595 842]'
                    . ' /Resources <</ExtGState <</GS1 5 0 R>>>>>>',
                5 => '<</Type /ExtGState /ca 1.0 /CA 0.3 /BM /Normal>>',
            ],
            self::withId(['Root' => new PdfIndirectReference(1, 0)]),
            PdfVersion::PDF_1_4,
        );

        $result = (new PdfAValidator())->validate($doc, PdfAConformance::PdfA1b);

        self::assertHasError('transparency.alpha', $result);
    }

    #[Test]
    public function validateNoTransparencyErrorForPdfA1bWithFullOpacity(): void
    {
        // ca=1.0, CA=1.0 → no alpha error
        $xmpData = self::xmp(PdfAConformance::PdfA1b);
        $doc = self::makeDoc(
            [
                1 => '<</Type /Catalog /Pages 2 0 R /Metadata 3 0 R>>',
                2 => '<</Type /Pages /Kids [4 0 R] /Count 1>>',
                3 => "<<>>\nstream\n{$xmpData}\nendstream",
                4 => '<</Type /Page /Parent 2 0 R /MediaBox [0 0 595 842]'
                    . ' /Resources <</ExtGState <</GS1 5 0 R>>>>>>',
                5 => '<</Type /ExtGState /ca 1.0 /CA 1.0 /BM /Normal>>',
            ],
            self::withId(['Root' => new PdfIndirectReference(1, 0)]),
            PdfVersion::PDF_1_4,
        );

        $result = (new PdfAValidator())->validate($doc, PdfAConformance::PdfA1b);

        $errorRules = array_map(static fn($e) => $e->rule, $result->getErrors());
        self::assertNotContains('transparency.alpha', $errorRules);
    }

    #[Test]
    public function validateEmitsSmaskErrorForPdfA1bWithExtGStateSMaskNotNone(): void
    {
        // ExtGState has /SMask that is not /None → error
        $xmpData = self::xmp(PdfAConformance::PdfA1b);
        $doc = self::makeDoc(
            [
                1 => '<</Type /Catalog /Pages 2 0 R /Metadata 3 0 R>>',
                2 => '<</Type /Pages /Kids [4 0 R] /Count 1>>',
                3 => "<<>>\nstream\n{$xmpData}\nendstream",
                4 => '<</Type /Page /Parent 2 0 R /MediaBox [0 0 595 842]'
                    . ' /Resources <</ExtGState <</GS1 5 0 R>>>>>>',
                5 => '<</Type /ExtGState /SMask <</Type /Mask /S /Luminosity>>>>',
            ],
            self::withId(['Root' => new PdfIndirectReference(1, 0)]),
            PdfVersion::PDF_1_4,
        );

        $result = (new PdfAValidator())->validate($doc, PdfAConformance::PdfA1b);

        self::assertHasError('transparency.smask', $result);
    }

    #[Test]
    public function validateNoSmaskErrorWhenExtGStateSmaskIsNone(): void
    {
        // /SMask = /None → not an error
        $xmpData = self::xmp(PdfAConformance::PdfA1b);
        $doc = self::makeDoc(
            [
                1 => '<</Type /Catalog /Pages 2 0 R /Metadata 3 0 R>>',
                2 => '<</Type /Pages /Kids [4 0 R] /Count 1>>',
                3 => "<<>>\nstream\n{$xmpData}\nendstream",
                4 => '<</Type /Page /Parent 2 0 R /MediaBox [0 0 595 842]'
                    . ' /Resources <</ExtGState <</GS1 5 0 R>>>>>>',
                5 => '<</Type /ExtGState /SMask /None>>',
            ],
            self::withId(['Root' => new PdfIndirectReference(1, 0)]),
            PdfVersion::PDF_1_4,
        );

        $result = (new PdfAValidator())->validate($doc, PdfAConformance::PdfA1b);

        $errorRules = array_map(static fn($e) => $e->rule, $result->getErrors());
        self::assertNotContains('transparency.smask', $errorRules);
    }

    #[Test]
    public function validateEmitsSmaskErrorForPdfA1bImageWithSMask(): void
    {
        // Image XObject has /SMask → forbidden in PDF/A-1b
        $xmpData = self::xmp(PdfAConformance::PdfA1b);
        $doc = self::makeDoc(
            [
                1 => '<</Type /Catalog /Pages 2 0 R /Metadata 3 0 R>>',
                2 => '<</Type /Pages /Kids [4 0 R] /Count 1>>',
                3 => "<<>>\nstream\n{$xmpData}\nendstream",
                4 => '<</Type /Page /Parent 2 0 R /MediaBox [0 0 595 842]'
                    . ' /Resources <</XObject <</Img1 5 0 R>>>>>>',
                5 => '<</Type /XObject /Subtype /Image /Width 1 /Height 1'
                    . ' /ColorSpace /DeviceRGB /BitsPerComponent 8 /SMask 6 0 R>>'
                    . "\nstream\n\x00\x00\x00\nendstream",
                6 => '<</Type /XObject /Subtype /Image /Width 1 /Height 1'
                    . ' /ColorSpace /DeviceGray /BitsPerComponent 8>>'
                    . "\nstream\n\xFF\nendstream",
            ],
            self::withId(['Root' => new PdfIndirectReference(1, 0)]),
            PdfVersion::PDF_1_4,
        );

        $result = (new PdfAValidator())->validate($doc, PdfAConformance::PdfA1b);

        self::assertHasError('transparency.smask', $result);
    }

    #[Test]
    public function validateEmitsTransparencyGroupErrorForPdfA1bFormXObjectWithGroup(): void
    {
        // Form XObject has /Group entry → transparency group forbidden in PDF/A-1b
        $xmpData = self::xmp(PdfAConformance::PdfA1b);
        $doc = self::makeDoc(
            [
                1 => '<</Type /Catalog /Pages 2 0 R /Metadata 3 0 R>>',
                2 => '<</Type /Pages /Kids [4 0 R] /Count 1>>',
                3 => "<<>>\nstream\n{$xmpData}\nendstream",
                4 => '<</Type /Page /Parent 2 0 R /MediaBox [0 0 595 842]'
                    . ' /Resources <</XObject <</Form1 5 0 R>>>>>>',
                5 => '<</Type /XObject /Subtype /Form /BBox [0 0 100 100]'
                    . ' /Group <</Type /Group /S /Transparency>>>>'
                    . "\nstream\n\nendstream",
            ],
            self::withId(['Root' => new PdfIndirectReference(1, 0)]),
            PdfVersion::PDF_1_4,
        );

        $result = (new PdfAValidator())->validate($doc, PdfAConformance::PdfA1b);

        self::assertHasError('transparency.group', $result);
    }

    #[Test]
    public function validateSkipsTransparencyCheckForPdfA2b(): void
    {
        // part===2 → checkPageTransparency is NOT called, no transparency errors
        $xmpData = self::xmp(PdfAConformance::PdfA2b);
        $doc = self::makeDoc(
            [
                1 => '<</Type /Catalog /Pages 2 0 R /Metadata 3 0 R>>',
                2 => '<</Type /Pages /Kids [4 0 R] /Count 1>>',
                3 => "<<>>\nstream\n{$xmpData}\nendstream",
                4 => '<</Type /Page /Parent 2 0 R /MediaBox [0 0 595 842]'
                    . ' /Resources <</ExtGState <</GS1 5 0 R>>>>>>',
                5 => '<</Type /ExtGState /ca 0.5>>', // sub-unity alpha
            ],
            self::withId(['Root' => new PdfIndirectReference(1, 0)]),
        );

        $result = (new PdfAValidator())->validate($doc, PdfAConformance::PdfA2b);

        $errorRules = array_map(static fn($e) => $e->rule, $result->getErrors());
        self::assertNotContains('transparency.alpha', $errorRules);
    }

    #[Test]
    public function validateHandlesExtGStateWhereResolvedIsNotDict(): void
    {
        // ExtGState entry resolves to non-dict → continue (no crash)
        $xmpData = self::xmp(PdfAConformance::PdfA1b);
        $doc = self::makeDoc(
            [
                1 => '<</Type /Catalog /Pages 2 0 R /Metadata 3 0 R>>',
                2 => '<</Type /Pages /Kids [4 0 R] /Count 1>>',
                3 => "<<>>\nstream\n{$xmpData}\nendstream",
                4 => '<</Type /Page /Parent 2 0 R /MediaBox [0 0 595 842]'
                    . ' /Resources <</ExtGState <</GS1 5 0 R>>>>>>',
                5 => '99', // integer, not dict
            ],
            self::withId(['Root' => new PdfIndirectReference(1, 0)]),
            PdfVersion::PDF_1_4,
        );

        $result = (new PdfAValidator())->validate($doc, PdfAConformance::PdfA1b);

        self::assertInstanceOf(PdfAValidationResult::class, $result);
    }

    #[Test]
    public function validateHandlesExtGStateWhereExtGsDictIsNotDict(): void
    {
        // ExtGState resource dict resolves to non-dict
        $xmpData = self::xmp(PdfAConformance::PdfA1b);
        $doc = self::makeDoc(
            [
                1 => '<</Type /Catalog /Pages 2 0 R /Metadata 3 0 R>>',
                2 => '<</Type /Pages /Kids [4 0 R] /Count 1>>',
                3 => "<<>>\nstream\n{$xmpData}\nendstream",
                4 => '<</Type /Page /Parent 2 0 R /MediaBox [0 0 595 842]'
                    . ' /Resources <</ExtGState 5 0 R>>>>',
                5 => '42', // integer, not dict — whole ExtGState dict is bogus
            ],
            self::withId(['Root' => new PdfIndirectReference(1, 0)]),
            PdfVersion::PDF_1_4,
        );

        $result = (new PdfAValidator())->validate($doc, PdfAConformance::PdfA1b);

        self::assertInstanceOf(PdfAValidationResult::class, $result);
    }

    #[Test]
    public function validateHandlesXObjectWhereResolvedIsNotStream(): void
    {
        // XObject entry resolves to non-stream (dict) → continue (not image or form)
        $xmpData = self::xmp(PdfAConformance::PdfA1b);
        $doc = self::makeDoc(
            [
                1 => '<</Type /Catalog /Pages 2 0 R /Metadata 3 0 R>>',
                2 => '<</Type /Pages /Kids [4 0 R] /Count 1>>',
                3 => "<<>>\nstream\n{$xmpData}\nendstream",
                4 => '<</Type /Page /Parent 2 0 R /MediaBox [0 0 595 842]'
                    . ' /Resources <</XObject <</X1 5 0 R>>>>>>',
                5 => '<</NotAStream /True>>', // dict, not stream
            ],
            self::withId(['Root' => new PdfIndirectReference(1, 0)]),
            PdfVersion::PDF_1_4,
        );

        $result = (new PdfAValidator())->validate($doc, PdfAConformance::PdfA1b);

        self::assertInstanceOf(PdfAValidationResult::class, $result);
    }

    #[Test]
    public function validateHandlesXObjectDictWhereResolvedIsNotDict(): void
    {
        // The XObject resource dict itself resolves to non-dict
        $xmpData = self::xmp(PdfAConformance::PdfA1b);
        $doc = self::makeDoc(
            [
                1 => '<</Type /Catalog /Pages 2 0 R /Metadata 3 0 R>>',
                2 => '<</Type /Pages /Kids [4 0 R] /Count 1>>',
                3 => "<<>>\nstream\n{$xmpData}\nendstream",
                4 => '<</Type /Page /Parent 2 0 R /MediaBox [0 0 595 842]'
                    . ' /Resources <</XObject 5 0 R>>>>',
                5 => '42', // integer, not dict
            ],
            self::withId(['Root' => new PdfIndirectReference(1, 0)]),
            PdfVersion::PDF_1_4,
        );

        $result = (new PdfAValidator())->validate($doc, PdfAConformance::PdfA1b);

        self::assertInstanceOf(PdfAValidationResult::class, $result);
    }

    // =========================================================================
    // validateFile (static entry point)
    // =========================================================================

    #[Test]
    public function validateFileOpensFileAndReturnsResult(): void
    {
        // Build a minimal compliant doc, serialize to a temp file, validate via static method
        $xmpData = self::xmp(PdfAConformance::PdfA2b);
        $content = '';
        $objects = [
            1 => '<</Type /Catalog /Pages 2 0 R /Metadata 3 0 R>>',
            2 => '<</Type /Pages /Kids [] /Count 0>>',
            3 => "<<>>\nstream\n{$xmpData}\nendstream",
        ];

        $xref = [];
        ksort($objects);

        foreach ($objects as $num => $raw) {
            $xref[$num] = strlen($content);
            $content .= "{$num} 0 obj\n{$raw}\nendobj\n";
        }

        $id = bin2hex(random_bytes(8));
        $pdf = "%PDF-1.7\n";
        // re-compute offsets accounting for header
        $headerLen = strlen($pdf);
        $content2 = '';
        $xref2 = [];

        foreach ($objects as $num => $raw) {
            $xref2[$num] = $headerLen + strlen($content2);
            $content2 .= "{$num} 0 obj\n{$raw}\nendobj\n";
        }

        $xrefStart2 = $headerLen + strlen($content2);
        $xrefBody2 = "xref\n0 4\n0000000000 65535 f \n";

        for ($i = 1; $i <= 3; $i++) {
            $xrefBody2 .= sprintf("%010d 00000 n \n", $xref2[$i]);
        }

        $pdf .= $content2 . $xrefBody2
            . "trailer\n<</Size 4 /Root 1 0 R /ID [<{$id}><{$id}>]>>\n"
            . "startxref\n{$xrefStart2}\n%%EOF";

        $tmpFile = tempnam(sys_get_temp_dir(), 'phppdf_test_');
        file_put_contents($tmpFile, $pdf);

        try {
            $result = PdfAValidator::validateFile($tmpFile, PdfAConformance::PdfA2b);

            self::assertInstanceOf(PdfAValidationResult::class, $result);
            self::assertSame(PdfAConformance::PdfA2b, $result->conformance);
        } finally {
            unlink($tmpFile);
        }
    }

    /**
     * Builds a minimal in-memory PDF string with exact byte offsets, creates a
     * PdfLexer from it, and returns a PdfReadDocument ready for the validator.
     *
     * @param array<int, string> $objects        [objectNumber => rawPdfSyntax]
     *                                                   e.g. [1 => '<</Type /Catalog ...>>']
     * @param array<string, \PhpPdf\Object\PdfObject> $trailerEntries
     *                                     Direct PdfObject entries for the trailer dict.
     */
    private static function makeDoc(
        array $objects,
        array $trailerEntries,
        PdfVersion $version = PdfVersion::PDF_1_7,
    ): PdfReadDocument {
        $content = '';
        $xref = [];

        ksort($objects);

        foreach ($objects as $num => $raw) {
            $xref[$num] = ['offset' => strlen($content), 'generation' => 0, 'type' => 'n'];
            $content .= "{$num} 0 obj\n{$raw}\nendobj\n";
        }

        return new PdfReadDocument(
            PdfLexer::fromString($content),
            $xref,
            new PdfDictionary($trailerEntries),
            $version,
        );
    }

    /**
     * Adds a /ID array with two 16-byte hex strings to trailer entries.
     *
     * @param array<string, \PhpPdf\Object\PdfObject> $entries
     * @return array<string, \PhpPdf\Object\PdfObject>
     */
    private static function withId(array $entries): array
    {
        $id = str_repeat('A', 16);
        $entries['ID'] = new PdfArray([new PdfString($id), new PdfString($id)]);

        return $entries;
    }

    /**
     * Minimal valid XMP string that satisfies every checkXmpMetadata() assertion
     * for the given conformance.
     */
    private static function xmp(PdfAConformance $c): string
    {
        $part = $c->part();
        $level = $c->conformanceLevel();

        return <<<XMP
<?xpacket begin="" id="W5M0MpCehiHzreSzNTczkc9d"?>
<x:xmpmeta xmlns:x="adobe:ns:meta/">
  <rdf:RDF xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#">
    <rdf:Description xmlns:pdfaid="http://www.aiim.org/pdfa/ns/id/">
      <pdfaid:part>{$part}</pdfaid:part>
      <pdfaid:conformance>{$level}</pdfaid:conformance>
    </rdf:Description>
    <rdf:Description xmlns:dc="http://purl.org/dc/elements/1.1/">
      <dc:format>application/pdf</dc:format>
    </rdf:Description>
    <rdf:Description xmlns:xmp="http://ns.adobe.com/xap/1.0/">
      <xmp:CreateDate>2024-01-01T00:00:00+00:00</xmp:CreateDate>
    </rdf:Description>
  </rdf:RDF>
</x:xmpmeta>
<?xpacket end="w"?>
XMP;
    }

    /** Builds a minimal compliant doc (no fonts, no pages, correct XMP). */
    private static function minimalCompliantDoc(PdfAConformance $c): PdfReadDocument
    {
        $xmpData = self::xmp($c);

        // Object layout: 1=Catalog, 2=Pages, 3=Metadata stream
        return self::makeDoc(
            [
                1 => '<</Type /Catalog /Pages 2 0 R /Metadata 3 0 R>>',
                2 => '<</Type /Pages /Kids [] /Count 0>>',
                3 => "<<>>\nstream\n{$xmpData}\nendstream",
            ],
            self::withId(['Root' => new PdfIndirectReference(1, 0)]),
            $c->part() === 1 ? PdfVersion::PDF_1_4 : PdfVersion::PDF_1_7,
        );
    }

    // =========================================================================
    // Assertion helpers
    // =========================================================================

    private static function assertHasError(string $rule, PdfAValidationResult $result, string $msg = ''): void
    {
        $rules = array_map(static fn($i) => $i->rule, $result->getErrors());
        self::assertContains(
            $rule,
            $rules,
            "Expected error '{$rule}' not found. {$msg}\nErrors: " . implode(', ', $rules),
        );
    }

    private static function assertHasWarning(string $rule, PdfAValidationResult $result): void
    {
        $rules = array_map(static fn($i) => $i->rule, $result->getWarnings());
        self::assertContains(
            $rule,
            $rules,
            "Expected warning '{$rule}' not found. Warnings: " . implode(', ', $rules),
        );
    }

    private static function assertNoError(string $rule, PdfAValidationResult $result): void
    {
        $rules = array_map(static fn($i) => $i->rule, $result->getErrors());
        self::assertNotContains($rule, $rules);
    }
}
