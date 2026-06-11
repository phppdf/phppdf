<?php

declare(strict_types=1);

namespace PhpPdf\Signing\PdfSignatureConfig;

use PhpPdf\Signing\PdfSignatureConfig;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(PdfSignatureConfig::class)]
#[CoversMethod(PdfSignatureConfig::class, 'location')]
final class LocationTest extends TestCase
{
    #[Test]
    public function locationIsNullByDefault(): void
    {
        // Arrange / Act
        $config = new PdfSignatureConfig();

        // Assert
        self::assertNull($config->getLocation());
    }

    #[Test]
    public function locationStoresValue(): void
    {
        // Arrange
        $config = new PdfSignatureConfig();

        // Act
        $config->location('Amsterdam');

        // Assert
        self::assertSame('Amsterdam', $config->getLocation());
    }

    #[Test]
    public function locationReturnsSelf(): void
    {
        // Arrange
        $config = new PdfSignatureConfig();

        // Act
        $result = $config->location('Amsterdam');

        // Assert
        self::assertSame($config, $result);
    }
}
