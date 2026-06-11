<?php

declare(strict_types=1);

namespace PhpPdf\Document\PdfDocumentInfo;

use DateTimeImmutable;
use DateTimeInterface;
use PhpPdf\Document\PdfDocumentInfo;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(PdfDocumentInfo::class)]
#[CoversMethod(PdfDocumentInfo::class, 'creationDate')]
final class CreationDateTest extends TestCase
{
    #[Test]
    public function creationDateIsSetByDefault(): void
    {
        // Arrange / Act
        $info = new PdfDocumentInfo();

        // Assert
        self::assertInstanceOf(DateTimeInterface::class, $info->getCreationDate());
    }

    #[Test]
    public function creationDateStoresValue(): void
    {
        // Arrange
        $info = new PdfDocumentInfo();
        $date = new DateTimeImmutable('2024-06-01T12:00:00Z');

        // Act
        $info->creationDate($date);

        // Assert
        self::assertSame($date, $info->getCreationDate());
    }

    #[Test]
    public function creationDateReturnsSelf(): void
    {
        // Arrange
        $info = new PdfDocumentInfo();

        // Act
        $result = $info->creationDate(new DateTimeImmutable());

        // Assert
        self::assertSame($info, $result);
    }
}
