<?php

declare(strict_types=1);

namespace PhpPdf\Encryption\PdfStandardSecurityHandler;

use PhpPdf\Encryption\PdfEncryptionConfig;
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
#[CoversMethod(PdfStandardSecurityHandler::class, 'buildEncryptionDictionary')]
#[UsesClass(PdfEncryptionConfig::class)]
#[UsesClass(PdfPermissions::class)]
#[UsesClass(PdfDictionary::class)]
#[UsesClass(PdfHexString::class)]
#[UsesClass(PdfInteger::class)]
#[UsesClass(PdfName::class)]
final class BuildEncryptionDictionaryTest extends TestCase
{
    private PdfDictionary $dict;

    #[Test]
    public function buildEncryptionDictionaryReturnsPdfDictionary(): void
    {
        // Arrange / Act / Assert
        self::assertInstanceOf(PdfDictionary::class, $this->dict);
    }

    #[Test]
    public function dictionaryHasStandardFilter(): void
    {
        // Arrange / Act
        $filter = $this->dict->get('Filter');

        // Assert
        self::assertInstanceOf(PdfName::class, $filter);
        self::assertSame('Standard', $filter->getValue());
    }

    #[Test]
    public function dictionaryVersionIsV4(): void
    {
        // Arrange / Act
        $v = $this->dict->get('V');

        // Assert
        self::assertInstanceOf(PdfInteger::class, $v);
        self::assertSame(4, $v->getValue());
    }

    #[Test]
    public function dictionaryRevisionIsR4(): void
    {
        // Arrange / Act
        $r = $this->dict->get('R');

        // Assert
        self::assertInstanceOf(PdfInteger::class, $r);
        self::assertSame(4, $r->getValue());
    }

    #[Test]
    public function dictionaryKeyLengthIs128Bits(): void
    {
        // Arrange / Act
        $length = $this->dict->get('Length');

        // Assert
        self::assertInstanceOf(PdfInteger::class, $length);
        self::assertSame(128, $length->getValue());
    }

    #[Test]
    public function dictionaryOwnerEntryIs32Bytes(): void
    {
        // Arrange / Act
        $O = $this->dict->get('O');

        // Assert
        self::assertInstanceOf(PdfHexString::class, $O);
        self::assertSame(32, strlen($O->getBinary()));
    }

    #[Test]
    public function dictionaryUserEntryIs32Bytes(): void
    {
        // Arrange / Act
        $U = $this->dict->get('U');

        // Assert
        self::assertInstanceOf(PdfHexString::class, $U);
        self::assertSame(32, strlen($U->getBinary()));
    }

    #[Test]
    public function dictionaryHasCryptFilterWithAesV2(): void
    {
        // Arrange / Act
        $cf = $this->dict->get('CF');

        // Assert
        self::assertInstanceOf(PdfDictionary::class, $cf);
        $stdCf = $cf->get('StdCF');
        self::assertInstanceOf(PdfDictionary::class, $stdCf);
        $cfm = $stdCf->get('CFM');
        self::assertInstanceOf(PdfName::class, $cfm);
        self::assertSame('AESV2', $cfm->getValue());
    }

    #[Test]
    public function dictionaryStreamFilterIsStdCF(): void
    {
        // Arrange / Act
        $stmF = $this->dict->get('StmF');

        // Assert
        self::assertInstanceOf(PdfName::class, $stmF);
        self::assertSame('StdCF', $stmF->getValue());
    }

    #[Test]
    public function dictionaryStringFilterIsStdCF(): void
    {
        // Arrange / Act
        $strF = $this->dict->get('StrF');

        // Assert
        self::assertInstanceOf(PdfName::class, $strF);
        self::assertSame('StdCF', $strF->getValue());
    }

    #[Test]
    public function dictionaryPermissionsMatchConfig(): void
    {
        // Arrange
        $permissions = PdfPermissions::none()->allowPrinting();
        $config = (new PdfEncryptionConfig())
            ->userPassword('u')
            ->permissions($permissions);
        $handler = new PdfStandardSecurityHandler($config, str_repeat("\x02", 16));

        // Act
        $dict = $handler->buildEncryptionDictionary();
        $P = $dict->get('P');

        // Assert
        self::assertInstanceOf(PdfInteger::class, $P);
        self::assertSame($permissions->toInt(), $P->getValue());
    }

    protected function setUp(): void
    {
        $config = (new PdfEncryptionConfig())
            ->userPassword('user')
            ->ownerPassword('owner');

        $handler = new PdfStandardSecurityHandler($config, str_repeat("\x01", 16));
        $this->dict = $handler->buildEncryptionDictionary();
    }
}
