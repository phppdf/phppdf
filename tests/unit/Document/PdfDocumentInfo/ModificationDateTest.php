<?php

declare(strict_types=1);

namespace PhpPdf\Document\PdfDocumentInfo;

use DateTimeImmutable;
use PhpPdf\Document\PdfDocumentInfo;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(PdfDocumentInfo::class)]
#[CoversMethod(PdfDocumentInfo::class, 'modificationDate')]
final class ModificationDateTest extends TestCase
{
    #[Test]
    public function modificationDateIsNullByDefault(): void
    {
        // Arrange / Act
        $info = new PdfDocumentInfo();

        // Assert
        self::assertNull($info->getModificationDate());
    }

    #[Test]
    public function modificationDateStoresValue(): void
    {
        // Arrange
        $info = new PdfDocumentInfo();
        $date = new DateTimeImmutable('2024-09-15T08:30:00Z');

        // Act
        $info->modificationDate($date);

        // Assert
        self::assertSame($date, $info->getModificationDate());
    }

    #[Test]
    public function modificationDateReturnsSelf(): void
    {
        // Arrange
        $info = new PdfDocumentInfo();

        // Act
        $result = $info->modificationDate(new DateTimeImmutable());

        // Assert
        self::assertSame($info, $result);
    }
}
