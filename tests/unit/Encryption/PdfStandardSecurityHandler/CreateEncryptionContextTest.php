<?php

declare(strict_types=1);

namespace PhpPdf\Encryption\PdfStandardSecurityHandler;

use PhpPdf\Encryption\PdfEncryptionConfig;
use PhpPdf\Encryption\PdfEncryptionContext;
use PhpPdf\Encryption\PdfPermissions;
use PhpPdf\Encryption\PdfStandardSecurityHandler;
use PhpPdf\Object\PdfDictionary;
use PhpPdf\Object\PdfHexString;
use PhpPdf\Object\PdfInteger;
use PhpPdf\Object\PdfName;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(PdfStandardSecurityHandler::class)]
#[CoversMethod(PdfStandardSecurityHandler::class, 'createEncryptionContext')]
#[UsesClass(PdfEncryptionConfig::class)]
#[UsesClass(PdfEncryptionContext::class)]
#[UsesClass(PdfPermissions::class)]
#[UsesClass(PdfDictionary::class)]
#[UsesClass(PdfHexString::class)]
#[UsesClass(PdfInteger::class)]
#[UsesClass(PdfName::class)]
final class CreateEncryptionContextTest extends TestCase
{
    #[Test]
    public function createEncryptionContextReturnsPdfEncryptionContext(): void
    {
        // Arrange
        $config = (new PdfEncryptionConfig())
            ->userPassword('user')
            ->ownerPassword('owner');
        $handler = new PdfStandardSecurityHandler($config, str_repeat("\x01", 16));

        // Act
        $context = $handler->createEncryptionContext();

        // Assert
        self::assertInstanceOf(PdfEncryptionContext::class, $context);
    }

    #[Test]
    public function createEncryptionContextReturnsNewInstanceEachCall(): void
    {
        // Arrange
        $config = (new PdfEncryptionConfig())->userPassword('user');
        $handler = new PdfStandardSecurityHandler($config, str_repeat("\x01", 16));

        // Act
        $ctx1 = $handler->createEncryptionContext();
        $ctx2 = $handler->createEncryptionContext();

        // Assert
        self::assertNotSame($ctx1, $ctx2);
    }
}
