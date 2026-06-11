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
#[CoversMethod(PdfEncryptionConfig::class, 'ownerPassword')]
#[UsesClass(PdfPermissions::class)]
final class OwnerPasswordTest extends TestCase
{
    #[Test]
    public function ownerPasswordReturnsSelf(): void
    {
        // Arrange
        $config = new PdfEncryptionConfig();

        // Act
        $result = $config->ownerPassword('admin');

        // Assert
        self::assertSame($config, $result);
    }

    #[Test]
    public function ownerPasswordStoresValue(): void
    {
        // Arrange
        $config = new PdfEncryptionConfig();

        // Act
        $config->ownerPassword('admin');

        // Assert
        self::assertSame('admin', $config->getOwnerPassword());
    }
}
