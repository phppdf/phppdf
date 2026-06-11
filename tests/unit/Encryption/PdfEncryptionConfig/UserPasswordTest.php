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
#[CoversMethod(PdfEncryptionConfig::class, 'userPassword')]
#[UsesClass(PdfPermissions::class)]
final class UserPasswordTest extends TestCase
{
    #[Test]
    public function userPasswordDefaultsToEmptyString(): void
    {
        // Arrange / Act
        $config = new PdfEncryptionConfig();

        // Assert
        self::assertSame('', $config->getUserPassword());
    }

    #[Test]
    public function userPasswordReturnsSelf(): void
    {
        // Arrange
        $config = new PdfEncryptionConfig();

        // Act
        $result = $config->userPassword('secret');

        // Assert
        self::assertSame($config, $result);
    }

    #[Test]
    public function userPasswordStoresValue(): void
    {
        // Arrange
        $config = new PdfEncryptionConfig();

        // Act
        $config->userPassword('open');

        // Assert
        self::assertSame('open', $config->getUserPassword());
    }
}
