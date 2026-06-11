<?php

declare(strict_types=1);

namespace PhpPdf\Document\PdfDocumentInfo;

use PhpPdf\Document\PdfDocumentInfo;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(PdfDocumentInfo::class)]
#[CoversMethod(PdfDocumentInfo::class, 'subject')]
final class SubjectTest extends TestCase
{
    #[Test]
    public function subjectIsNullByDefault(): void
    {
        // Arrange / Act
        $info = new PdfDocumentInfo();

        // Assert
        self::assertNull($info->getSubject());
    }

    #[Test]
    public function subjectStoresValue(): void
    {
        // Arrange
        $info = new PdfDocumentInfo();

        // Act
        $info->subject('Q3 Financial Summary');

        // Assert
        self::assertSame('Q3 Financial Summary', $info->getSubject());
    }

    #[Test]
    public function subjectReturnsSelf(): void
    {
        // Arrange
        $info = new PdfDocumentInfo();

        // Act
        $result = $info->subject('Q3 Financial Summary');

        // Assert
        self::assertSame($info, $result);
    }
}
