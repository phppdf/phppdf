<?php

declare(strict_types=1);

namespace PhpPdf\Signing\PdfSignatureConfig;

use PhpPdf\Signing\PdfSignatureConfig;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(PdfSignatureConfig::class)]
#[CoversMethod(PdfSignatureConfig::class, 'reason')]
final class ReasonTest extends TestCase
{
    #[Test]
    public function reasonIsNullByDefault(): void
    {
        // Arrange / Act
        $config = new PdfSignatureConfig();

        // Assert
        self::assertNull($config->getReason());
    }

    #[Test]
    public function reasonStoresValue(): void
    {
        // Arrange
        $config = new PdfSignatureConfig();

        // Act
        $config->reason('Approved');

        // Assert
        self::assertSame('Approved', $config->getReason());
    }

    #[Test]
    public function reasonReturnsSelf(): void
    {
        // Arrange
        $config = new PdfSignatureConfig();

        // Act
        $result = $config->reason('Approved');

        // Assert
        self::assertSame($config, $result);
    }
}
