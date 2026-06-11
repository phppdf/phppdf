<?php

declare(strict_types=1);

namespace PhpPdf\Builder\PdfDocumentBuilder;

use PhpPdf\Builder\PdfDocumentBuilder;
use PhpPdf\Document\PdfDocument;
use PhpPdf\Encryption\PdfEncryptionConfig;
use PhpPdf\Encryption\PdfEncryptionContext;
use PhpPdf\Encryption\PdfPermissions;
use PhpPdf\Encryption\PdfStandardSecurityHandler;
use PhpPdf\Object\PdfArray;
use PhpPdf\Object\PdfDictionary;
use PhpPdf\Object\PdfHexString;
use PhpPdf\Object\PdfIndirectObject;
use PhpPdf\Object\PdfIndirectReference;
use PhpPdf\Object\PdfInteger;
use PhpPdf\Object\PdfName;
use PhpPdf\Object\PdfObjectRegistry;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(PdfDocumentBuilder::class)]
#[CoversMethod(PdfDocumentBuilder::class, 'encrypt')]
#[UsesClass(PdfDocument::class)]
#[UsesClass(PdfArray::class)]
#[UsesClass(PdfDictionary::class)]
#[UsesClass(PdfEncryptionConfig::class)]
#[UsesClass(PdfEncryptionContext::class)]
#[UsesClass(PdfHexString::class)]
#[UsesClass(PdfPermissions::class)]
#[UsesClass(PdfIndirectObject::class)]
#[UsesClass(PdfIndirectReference::class)]
#[UsesClass(PdfInteger::class)]
#[UsesClass(PdfName::class)]
#[UsesClass(PdfObjectRegistry::class)]
#[UsesClass(PdfStandardSecurityHandler::class)]
final class EncryptTest extends TestCase
{
    #[Test]
    public function encryptReturnsSelf(): void
    {
        // Arrange
        $builder = new PdfDocumentBuilder();

        // Act
        $result = $builder->encrypt(new PdfEncryptionConfig());

        // Assert
        self::assertSame($builder, $result);
    }

    #[Test]
    public function encryptIsReflectedInDocument(): void
    {
        // Arrange / Act
        $document = (new PdfDocumentBuilder())
            ->encrypt(new PdfEncryptionConfig())
            ->build();

        // Assert
        self::assertNotNull($document->getEncryptDict());
        self::assertNotNull($document->getEncryptionContext());
        self::assertNotNull($document->getDocumentId());
        self::assertSame(16, strlen($document->getDocumentId()));
    }
}
