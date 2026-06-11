<?php

declare(strict_types=1);

namespace PhpPdf\Document\PdfDocumentInfo;

use PhpPdf\Document\PdfDocumentInfo;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(PdfDocumentInfo::class)]
#[CoversMethod(PdfDocumentInfo::class, 'producer')]
final class ProducerTest extends TestCase
{
    #[Test]
    public function producerDefaultsToPhpPdf(): void
    {
        // Arrange / Act
        $info = new PdfDocumentInfo();

        // Assert
        self::assertSame('phppdf/phppdf', $info->getProducer());
    }

    #[Test]
    public function producerStoresValue(): void
    {
        // Arrange
        $info = new PdfDocumentInfo();

        // Act
        $info->producer('MyApp 1.0');

        // Assert
        self::assertSame('MyApp 1.0', $info->getProducer());
    }

    #[Test]
    public function producerReturnsSelf(): void
    {
        // Arrange
        $info = new PdfDocumentInfo();

        // Act
        $result = $info->producer('MyApp 1.0');

        // Assert
        self::assertSame($info, $result);
    }
}
