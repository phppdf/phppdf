<?php

declare(strict_types=1);

namespace PhpPdf\Builder\PdfDocumentBuilder;

use InvalidArgumentException;
use PhpPdf\Builder\PdfDocumentBuilder;
use PhpPdf\Compliance\PdfAConformance;
use PhpPdf\Compliance\PdfAMetadataBuilder;
use PhpPdf\Document\PdfDocument;
use PhpPdf\Document\PdfDocumentInfo;
use PhpPdf\Encryption\PdfEncryptionConfig;
use PhpPdf\Encryption\PdfPermissions;
use PhpPdf\Object\PdfArray;
use PhpPdf\Object\PdfDate;
use PhpPdf\Object\PdfDictionary;
use PhpPdf\Object\PdfIndirectObject;
use PhpPdf\Object\PdfIndirectReference;
use PhpPdf\Object\PdfInteger;
use PhpPdf\Object\PdfName;
use PhpPdf\Object\PdfObjectRegistry;
use PhpPdf\Object\PdfRawStreamData;
use PhpPdf\Object\PdfStream;
use PhpPdf\Object\PdfString;
use PhpPdf\Object\PdfXmpMetadataStream;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(PdfDocumentBuilder::class)]
#[CoversMethod(PdfDocumentBuilder::class, 'conformTo')]
#[UsesClass(PdfAConformance::class)]
#[UsesClass(PdfAMetadataBuilder::class)]
#[UsesClass(PdfDocument::class)]
#[UsesClass(PdfArray::class)]
#[UsesClass(PdfDate::class)]
#[UsesClass(PdfDictionary::class)]
#[UsesClass(PdfDocumentInfo::class)]
#[UsesClass(PdfEncryptionConfig::class)]
#[UsesClass(PdfPermissions::class)]
#[UsesClass(PdfIndirectObject::class)]
#[UsesClass(PdfIndirectReference::class)]
#[UsesClass(PdfInteger::class)]
#[UsesClass(PdfName::class)]
#[UsesClass(PdfObjectRegistry::class)]
#[UsesClass(PdfRawStreamData::class)]
#[UsesClass(PdfStream::class)]
#[UsesClass(PdfString::class)]
#[UsesClass(PdfXmpMetadataStream::class)]
final class ConformToTest extends TestCase
{
    #[Test]
    public function conformToReturnsSelf(): void
    {
        // Arrange
        $builder = new PdfDocumentBuilder();

        // Act
        $result = $builder->conformTo(PdfAConformance::PdfA1b);

        // Assert
        self::assertSame($builder, $result);
    }

    #[Test]
    public function conformToWithEncryptionThrowsException(): void
    {
        // Arrange
        $builder = (new PdfDocumentBuilder())
            ->conformTo(PdfAConformance::PdfA1b)
            ->encrypt(new PdfEncryptionConfig());

        // Assert / Act
        self::expectException(InvalidArgumentException::class);
        $builder->build();
    }

    #[Test]
    public function conformToWithoutEncryptionSetsDocumentId(): void
    {
        // Arrange / Act — conformance requires /ID in the trailer
        $document = (new PdfDocumentBuilder())
            ->conformTo(PdfAConformance::PdfA2b)
            ->build();

        // Assert — documentId is set (16 random bytes)
        self::assertNotNull($document->getDocumentId());
        self::assertSame(16, strlen($document->getDocumentId()));
    }

    #[Test]
    public function conformToWithInfoBuildsXmpMetadata(): void
    {
        // Arrange / Act — info + conformTo covers the info properties in PdfAMetadataBuilder
        $document = (new PdfDocumentBuilder())
            ->conformTo(PdfAConformance::PdfA1b)
            ->info(
                (new PdfDocumentInfo())
                    ->title('Test')
                    ->author('Author')
                    ->subject('Subject'),
            )
            ->build();

        // Assert
        self::assertInstanceOf(PdfDocument::class, $document);
        self::assertNotNull($document->getDocumentId());
    }
}
