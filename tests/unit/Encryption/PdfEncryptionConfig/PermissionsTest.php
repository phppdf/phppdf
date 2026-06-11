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
#[CoversMethod(PdfEncryptionConfig::class, 'permissions')]
#[UsesClass(PdfPermissions::class)]
final class PermissionsTest extends TestCase
{
    #[Test]
    public function permissionsDefaultsToAll(): void
    {
        // Arrange / Act
        $config = new PdfEncryptionConfig();

        // Assert
        self::assertSame(PdfPermissions::all()->toInt(), $config->getPermissions()->toInt());
    }

    #[Test]
    public function permissionsReturnsSelf(): void
    {
        // Arrange
        $config = new PdfEncryptionConfig();

        // Act
        $result = $config->permissions(PdfPermissions::none());

        // Assert
        self::assertSame($config, $result);
    }

    #[Test]
    public function permissionsStoresValue(): void
    {
        // Arrange
        $config = new PdfEncryptionConfig();
        $custom = PdfPermissions::none()->allowPrinting();

        // Act
        $config->permissions($custom);

        // Assert
        self::assertSame($custom->toInt(), $config->getPermissions()->toInt());
    }
}
