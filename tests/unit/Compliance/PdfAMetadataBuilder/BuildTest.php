<?php

declare(strict_types=1);

namespace PhpPdf\Compliance\PdfAMetadataBuilder;

use DateTimeImmutable;
use PhpPdf\Compliance\PdfAConformance;
use PhpPdf\Compliance\PdfAMetadataBuilder;
use PhpPdf\Document\PdfDocumentInfo;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(PdfAMetadataBuilder::class)]
#[CoversMethod(PdfAMetadataBuilder::class, 'build')]
#[UsesClass(PdfAConformance::class)]
#[UsesClass(PdfDocumentInfo::class)]
final class BuildTest extends TestCase
{
    #[Test]
    public function buildWithoutInfoIncludesPdfaidBlock(): void
    {
        $xml = (new PdfAMetadataBuilder())->build(PdfAConformance::PdfA2b);

        self::assertStringContainsString('<pdfaid:part>', $xml);
        self::assertStringContainsString('>2<', $xml);
        self::assertStringContainsString('<pdfaid:conformance>', $xml);
        self::assertStringContainsString('>B<', $xml);
        self::assertStringContainsString('application/pdf', $xml);
    }

    #[Test]
    public function buildWithInfoIncludesTitleAuthorSubject(): void
    {
        // Covers the title / author / subject branches (nullable properties)
        $info = (new PdfDocumentInfo())
            ->title('My Title')
            ->author('Jane Doe')
            ->subject('Test Subject');

        $xml = (new PdfAMetadataBuilder())->build(PdfAConformance::PdfA1b, $info);

        self::assertStringContainsString('My Title', $xml);
        self::assertStringContainsString('Jane Doe', $xml);
        self::assertStringContainsString('Test Subject', $xml);
        self::assertStringContainsString('>1<', $xml); // pdfaid:part = 1
        self::assertStringContainsString('>B<', $xml); // pdfaid:conformance = B
    }

    #[Test]
    public function buildWithInfoWithNullOptionalFieldsSkipsDcElements(): void
    {
        // Covers null branches: title/author/subject all absent
        $info = new PdfDocumentInfo(); // title, author, subject are null

        $xml = (new PdfAMetadataBuilder())->build(PdfAConformance::PdfA3b, $info);

        // pdfaid block must be present regardless
        self::assertStringContainsString('<pdfaid:part>', $xml);
        // dc:title, dc:creator, dc:description must NOT appear
        self::assertStringNotContainsString('dc:title', $xml);
        self::assertStringNotContainsString('dc:creator', $xml);
        self::assertStringNotContainsString('dc:description', $xml);
    }

    #[Test]
    public function buildIncludesXmpCreateAndModifyDates(): void
    {
        $date = new DateTimeImmutable('2024-06-15T10:30:00+00:00');
        $info = (new PdfDocumentInfo())
            ->creationDate($date)
            ->modificationDate($date);

        $xml = (new PdfAMetadataBuilder())->build(PdfAConformance::PdfA2b, $info);

        self::assertStringContainsString('2024-06-15T10:30:00', $xml);
        self::assertStringContainsString('xmp:CreateDate', $xml);
        self::assertStringContainsString('xmp:ModifyDate', $xml);
    }

    #[Test]
    public function buildWithInfoWithCreatorUsesCreatorToolValue(): void
    {
        // Covers the creator branch in xmpBlock
        $info = (new PdfDocumentInfo())->creator('MyApp 1.0');

        $xml = (new PdfAMetadataBuilder())->build(PdfAConformance::PdfA2b, $info);

        self::assertStringContainsString('MyApp 1.0', $xml);
    }

    #[Test]
    public function buildWithInfoWithModificationDateUsesIt(): void
    {
        // Covers the $modifyDate branch when modificationDate is separately set
        $create = new DateTimeImmutable('2024-01-01T00:00:00+00:00');
        $modify = new DateTimeImmutable('2024-06-01T00:00:00+00:00');
        $info = (new PdfDocumentInfo())
            ->creationDate($create)
            ->modificationDate($modify);

        $xml = (new PdfAMetadataBuilder())->build(PdfAConformance::PdfA2b, $info);

        self::assertStringContainsString('2024-01-01', $xml);
        self::assertStringContainsString('2024-06-01', $xml);
    }

    #[Test]
    public function buildWithSpecialCharsInTitleEscapesThem(): void
    {
        // Covers esc() → htmlspecialchars path with XML special chars
        $info = (new PdfDocumentInfo())->title('Test & <Demo> "Quotes"');

        $xml = (new PdfAMetadataBuilder())->build(PdfAConformance::PdfA2b, $info);

        self::assertStringContainsString('&amp;', $xml);
        self::assertStringContainsString('&lt;', $xml);
        self::assertStringContainsString('&gt;', $xml);
        self::assertStringContainsString('&quot;', $xml);
    }
}
