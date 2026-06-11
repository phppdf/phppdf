<?php

declare(strict_types=1);

namespace PhpPdf\Document\PdfDocumentInfo;

use PhpPdf\Document\PdfDocumentInfo;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(PdfDocumentInfo::class)]
#[CoversMethod(PdfDocumentInfo::class, 'creator')]
final class CreatorTest extends TestCase
{
    #[Test]
    public function creatorIsNullByDefault(): void
    {
        // Arrange / Act
        $info = new PdfDocumentInfo();

        // Assert
        self::assertNull($info->getCreator());
    }

    #[Test]
    public function creatorStoresValue(): void
    {
        // Arrange
        $info = new PdfDocumentInfo();

        // Act
        $info->creator('ReportApp 3.0');

        // Assert
        self::assertSame('ReportApp 3.0', $info->getCreator());
    }

    #[Test]
    public function creatorReturnsSelf(): void
    {
        // Arrange
        $info = new PdfDocumentInfo();

        // Act
        $result = $info->creator('ReportApp 3.0');

        // Assert
        self::assertSame($info, $result);
    }
}
