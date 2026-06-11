<?php

declare(strict_types=1);

namespace PhpPdf\Document\PdfDocumentInfo;

use PhpPdf\Document\PdfDocumentInfo;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(PdfDocumentInfo::class)]
#[CoversMethod(PdfDocumentInfo::class, 'keywords')]
final class KeywordsTest extends TestCase
{
    #[Test]
    public function keywordsIsNullByDefault(): void
    {
        // Arrange / Act
        $info = new PdfDocumentInfo();

        // Assert
        self::assertNull($info->getKeywords());
    }

    #[Test]
    public function keywordsStoresValue(): void
    {
        // Arrange
        $info = new PdfDocumentInfo();

        // Act
        $info->keywords('finance, annual, 2024');

        // Assert
        self::assertSame('finance, annual, 2024', $info->getKeywords());
    }

    #[Test]
    public function keywordsReturnsSelf(): void
    {
        // Arrange
        $info = new PdfDocumentInfo();

        // Act
        $result = $info->keywords('finance, annual, 2024');

        // Assert
        self::assertSame($info, $result);
    }
}
