<?php

declare(strict_types=1);

namespace PhpPdf\Encryption\PdfEncryptionConfig;

use PhpPdf\Encryption\PdfEncryptionConfig;
use PhpPdf\Encryption\PdfPermissions;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(PdfEncryptionConfig::class)]
#[CoversMethod(PdfEncryptionConfig::class, 'getOwnerPassword')]
#[UsesClass(PdfPermissions::class)]
final class GetOwnerPasswordTest extends TestCase
{
    #[Test]
    public function getOwnerPasswordFallsBackToUserPasswordWhenNotSet(): void
    {
        // Arrange
        $config = new PdfEncryptionConfig();
        $config->userPassword('open');

        // Act
        $ownerPassword = $config->getOwnerPassword();

        // Assert
        self::assertSame('open', $ownerPassword);
    }

    #[Test]
    public function getOwnerPasswordReturnsOwnValueWhenSet(): void
    {
        // Arrange
        $config = new PdfEncryptionConfig();
        $config->userPassword('open')->ownerPassword('admin');

        // Act
        $ownerPassword = $config->getOwnerPassword();

        // Assert
        self::assertSame('admin', $ownerPassword);
    }

    #[Test]
    public function getOwnerPasswordFallsBackToEmptyStringWhenBothUnset(): void
    {
        // Arrange
        $config = new PdfEncryptionConfig();

        // Act
        $ownerPassword = $config->getOwnerPassword();

        // Assert
        self::assertSame('', $ownerPassword);
    }
}
