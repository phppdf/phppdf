<?php

declare(strict_types=1);

namespace PhpPdf\Encryption\PdfDecryptionHandler;

use PhpPdf\Encryption\PdfDecryptionHandler;
use PhpPdf\Encryption\PdfEncryptionConfig;
use PhpPdf\Encryption\PdfEncryptionContext;
use PhpPdf\Encryption\PdfPermissions;
use PhpPdf\Encryption\PdfStandardSecurityHandler;
use PhpPdf\Object\PdfDictionary;
use PhpPdf\Object\PdfHexString;
use PhpPdf\Object\PdfInteger;
use PhpPdf\Object\PdfName;
use PhpPdf\Object\PdfString;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(PdfDecryptionHandler::class)]
#[CoversMethod(PdfDecryptionHandler::class, 'authenticate')]
#[UsesClass(PdfEncryptionConfig::class)]
#[UsesClass(PdfEncryptionContext::class)]
#[UsesClass(PdfPermissions::class)]
#[UsesClass(PdfStandardSecurityHandler::class)]
#[UsesClass(PdfDictionary::class)]
#[UsesClass(PdfHexString::class)]
#[UsesClass(PdfInteger::class)]
#[UsesClass(PdfName::class)]
#[UsesClass(PdfString::class)]
final class AuthenticateTest extends TestCase
{
    private const string FILE_ID = "\xDE\xAD\xBE\xEF\xCA\xFE\xBA\xBE\x01\x02\x03\x04\x05\x06\x07\x08";

    #[Test]
    public function authenticateWithCorrectUserPasswordReturnsContext(): void
    {
        // Arrange
        $dict = $this->buildAuthDict('open', 'admin');

        // Act
        $ctx = PdfDecryptionHandler::authenticate($dict, self::FILE_ID, 'open');

        // Assert
        self::assertInstanceOf(PdfEncryptionContext::class, $ctx);
    }

    #[Test]
    public function authenticateWithCorrectOwnerPasswordReturnsContext(): void
    {
        // Arrange
        $dict = $this->buildAuthDict('open', 'admin');

        // Act
        $ctx = PdfDecryptionHandler::authenticate($dict, self::FILE_ID, 'admin');

        // Assert
        self::assertInstanceOf(PdfEncryptionContext::class, $ctx);
    }

    #[Test]
    public function authenticateWithWrongPasswordReturnsNull(): void
    {
        // Arrange
        $dict = $this->buildAuthDict('open', 'admin');

        // Act
        $ctx = PdfDecryptionHandler::authenticate($dict, self::FILE_ID, 'wrong');

        // Assert
        self::assertNull($ctx);
    }

    #[Test]
    public function authenticateWithEmptyPasswordMatchesEmptyUserPassword(): void
    {
        // Arrange — no user password set (defaults to empty string)
        $dict = $this->buildAuthDict('', 'admin');

        // Act
        $ctx = PdfDecryptionHandler::authenticate($dict, self::FILE_ID, '');

        // Assert
        self::assertInstanceOf(PdfEncryptionContext::class, $ctx);
    }

    #[Test]
    public function authenticateReturnNullWhenOEntryMissing(): void
    {
        // Arrange — dict without O entry
        $dict = new PdfDictionary([
            'P' => new PdfInteger(-4),
            'U' => new PdfString(str_repeat("\x00", 32)),
        ]);

        // Act
        $ctx = PdfDecryptionHandler::authenticate($dict, self::FILE_ID, 'any');

        // Assert
        self::assertNull($ctx);
    }

    #[Test]
    public function authenticateReturnNullWhenUEntryMissing(): void
    {
        // Arrange — dict without U entry
        $dict = new PdfDictionary([
            'O' => new PdfString(str_repeat("\x00", 32)),
            'P' => new PdfInteger(-4),
        ]);

        // Act
        $ctx = PdfDecryptionHandler::authenticate($dict, self::FILE_ID, 'any');

        // Assert
        self::assertNull($ctx);
    }

    #[Test]
    public function authenticateReturnNullWhenPEntryMissing(): void
    {
        // Arrange — dict without P entry
        $dict = new PdfDictionary([
            'O' => new PdfString(str_repeat("\x00", 32)),
            'U' => new PdfString(str_repeat("\x00", 32)),
        ]);

        // Act
        $ctx = PdfDecryptionHandler::authenticate($dict, self::FILE_ID, 'any');

        // Assert
        self::assertNull($ctx);
    }

    #[Test]
    public function contextFromAuthenticateCanDecryptDataEncryptedByHandler(): void
    {
        // Arrange — build handler and decryption dict using the same credentials
        $config = (new PdfEncryptionConfig())
            ->userPassword('open')
            ->ownerPassword('admin');
        $handler = new PdfStandardSecurityHandler($config, self::FILE_ID);
        $encryptDict = $handler->buildEncryptionDictionary();

        $oValue = $encryptDict->get('O');
        $pValue = $encryptDict->get('P');
        $uValue = $encryptDict->get('U');
        self::assertInstanceOf(PdfHexString::class, $oValue);
        self::assertNotNull($pValue);
        self::assertInstanceOf(PdfHexString::class, $uValue);

        $authDict = new PdfDictionary([
            'O' => new PdfString($oValue->getBinary()),
            'P' => $pValue,
            'U' => new PdfString($uValue->getBinary()),
        ]);

        $writeContext = $handler->createEncryptionContext();
        $ciphertext = $writeContext->encrypt('Hello, world!', 1, 0);

        // Act
        $readContext = PdfDecryptionHandler::authenticate($authDict, self::FILE_ID, 'open');
        self::assertInstanceOf(PdfEncryptionContext::class, $readContext);
        $plaintext = $readContext->decrypt($ciphertext, 1, 0);

        // Assert
        self::assertSame('Hello, world!', $plaintext);
    }

    /**
     * Builds an encryption dict via PdfStandardSecurityHandler and converts
     * O/U from PdfHexString to PdfString so that PdfDecryptionHandler can
     * read them (it uses PdfString::getValue(), matching how the reader parses
     * binary hex strings from a PDF file).
     */
    private function buildAuthDict(string $userPassword, string $ownerPassword): PdfDictionary
    {
        $config = (new PdfEncryptionConfig())
            ->userPassword($userPassword)
            ->ownerPassword($ownerPassword);

        $handler = new PdfStandardSecurityHandler($config, self::FILE_ID);
        $dict = $handler->buildEncryptionDictionary();

        $oValue = $dict->get('O');
        $pValue = $dict->get('P');
        $uValue = $dict->get('U');
        self::assertInstanceOf(PdfHexString::class, $oValue);
        self::assertNotNull($pValue);
        self::assertInstanceOf(PdfHexString::class, $uValue);

        return new PdfDictionary([
            'O' => new PdfString($oValue->getBinary()),
            'P' => $pValue,
            'U' => new PdfString($uValue->getBinary()),
        ]);
    }
}
