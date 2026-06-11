<?php

declare(strict_types=1);

namespace PhpPdf\Document\PdfDocumentInfo;

use PhpPdf\Document\PdfDocumentInfo;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(PdfDocumentInfo::class)]
#[CoversMethod(PdfDocumentInfo::class, 'title')]
final class TitleTest extends TestCase
{
    #[Test]
    public function titleIsNullByDefault(): void
    {
        // Arrange / Act
        $info = new PdfDocumentInfo();

        // Assert
        self::assertNull($info->getTitle());
    }

    #[Test]
    public function titleStoresValue(): void
    {
        // Arrange
        $info = new PdfDocumentInfo();

        // Act
        $info->title('Annual Report 2024');

        // Assert
        self::assertSame('Annual Report 2024', $info->getTitle());
    }

    #[Test]
    public function titleReturnsSelf(): void
    {
        // Arrange
        $info = new PdfDocumentInfo();

        // Act
        $result = $info->title('Annual Report 2024');

        // Assert
        self::assertSame($info, $result);
    }
}
