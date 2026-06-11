<?php

declare(strict_types=1);

namespace PhpPdf\Signing\PdfSignatureConfig;

use PhpPdf\Signing\PdfSignatureConfig;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(PdfSignatureConfig::class)]
#[CoversMethod(PdfSignatureConfig::class, 'name')]
final class NameTest extends TestCase
{
    #[Test]
    public function nameIsNullByDefault(): void
    {
        // Arrange / Act
        $config = new PdfSignatureConfig();

        // Assert
        self::assertNull($config->getName());
    }

    #[Test]
    public function nameStoresValue(): void
    {
        // Arrange
        $config = new PdfSignatureConfig();

        // Act
        $config->name('Jane Smith');

        // Assert
        self::assertSame('Jane Smith', $config->getName());
    }

    #[Test]
    public function nameReturnsSelf(): void
    {
        // Arrange
        $config = new PdfSignatureConfig();

        // Act
        $result = $config->name('Jane Smith');

        // Assert
        self::assertSame($config, $result);
    }
}
