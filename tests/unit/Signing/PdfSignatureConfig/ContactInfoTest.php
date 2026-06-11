<?php

declare(strict_types=1);

namespace PhpPdf\Signing\PdfSignatureConfig;

use PhpPdf\Signing\PdfSignatureConfig;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(PdfSignatureConfig::class)]
#[CoversMethod(PdfSignatureConfig::class, 'contactInfo')]
final class ContactInfoTest extends TestCase
{
    #[Test]
    public function contactInfoIsNullByDefault(): void
    {
        // Arrange / Act
        $config = new PdfSignatureConfig();

        // Assert
        self::assertNull($config->getContactInfo());
    }

    #[Test]
    public function contactInfoStoresValue(): void
    {
        // Arrange
        $config = new PdfSignatureConfig();

        // Act
        $config->contactInfo('signer@example.com');

        // Assert
        self::assertSame('signer@example.com', $config->getContactInfo());
    }

    #[Test]
    public function contactInfoReturnsSelf(): void
    {
        // Arrange
        $config = new PdfSignatureConfig();

        // Act
        $result = $config->contactInfo('signer@example.com');

        // Assert
        self::assertSame($config, $result);
    }
}
