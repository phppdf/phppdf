<?php

declare(strict_types=1);

namespace PhpPdf\Serialization\PdfDocumentSerializer;

use DateTimeImmutable;
use DateTimeZone;
use PhpPdf\Document\PdfDocument;
use PhpPdf\Encryption\PdfEncryptionContext;
use PhpPdf\Object\PdfDate;
use PhpPdf\Object\PdfDictionary;
use PhpPdf\Object\PdfHexString;
use PhpPdf\Object\PdfIndirectObject;
use PhpPdf\Object\PdfIndirectReference;
use PhpPdf\Object\PdfInteger;
use PhpPdf\Object\PdfName;
use PhpPdf\Object\PdfObjectRegistry;
use PhpPdf\Object\PdfRawStreamData;
use PhpPdf\Object\PdfStream;
use PhpPdf\Object\PdfString;
use PhpPdf\Object\PdfVersion;
use PhpPdf\Output\PdfMemoryOutput;
use PhpPdf\Serialization\PdfDocumentSerializer;
use PhpPdf\Serialization\PdfStreamSerializer;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(PdfDocumentSerializer::class)]
#[CoversMethod(PdfDocumentSerializer::class, 'writeDocument')]
#[UsesClass(PdfDate::class)]
#[UsesClass(PdfDictionary::class)]
#[UsesClass(PdfDocument::class)]
#[UsesClass(PdfEncryptionContext::class)]
#[UsesClass(PdfHexString::class)]
#[UsesClass(PdfIndirectObject::class)]
#[UsesClass(PdfIndirectReference::class)]
#[UsesClass(PdfInteger::class)]
#[UsesClass(PdfMemoryOutput::class)]
#[UsesClass(PdfName::class)]
#[UsesClass(PdfObjectRegistry::class)]
#[UsesClass(PdfRawStreamData::class)]
#[UsesClass(PdfStream::class)]
#[UsesClass(PdfString::class)]
#[UsesClass(PdfStreamSerializer::class)]
final class WriteDocumentTest extends TestCase
{
    #[Test]
    public function writeDocumentProducesValidPdfStructure(): void
    {
        // Arrange
        [$registry, $catalogRef] = $this->buildCatalogRegistry();
        $document = new PdfDocument(objects: $registry, version: PdfVersion::PDF_1_7, catalog: $catalogRef, info: null);
        $output = new PdfMemoryOutput();
        $serializer = new PdfDocumentSerializer($output);

        // Act
        $serializer->writeDocument($document);

        // Assert
        $content = $output->getContent();
        self::assertStringStartsWith("%PDF-1.7\n", $content);
        self::assertStringContainsString('1 0 obj', $content);
        self::assertStringContainsString('xref', $content);
        self::assertStringContainsString('trailer', $content);
        self::assertStringContainsString('/Root 1 0 R', $content);
        self::assertStringContainsString('startxref', $content);
        self::assertStringEndsWith("%%EOF\n", $content);
    }

    #[Test]
    public function writeDocumentIncludesInfoReferenceInTrailer(): void
    {
        // Arrange
        $registry = new PdfObjectRegistry();
        $catalogRef = $registry->register(new PdfDictionary(['Type' => new PdfName('Catalog')]));
        $infoRef = $registry->register(new PdfDictionary(['Producer' => new PdfString('Test')]));
        $document = new PdfDocument(
            objects: $registry,
            version: PdfVersion::PDF_1_7,
            catalog: $catalogRef,
            info: $infoRef,
        );
        $output = new PdfMemoryOutput();
        $serializer = new PdfDocumentSerializer($output);

        // Act
        $serializer->writeDocument($document);

        // Assert
        self::assertStringContainsString(
            sprintf('/Info %d %d R', $infoRef->getObjectNumber(), $infoRef->getGenerationNumber()),
            $output->getContent(),
        );
    }

    #[Test]
    public function writeDocumentIncludesDocumentIdInTrailer(): void
    {
        // Arrange
        [$registry, $catalogRef] = $this->buildCatalogRegistry();
        $documentId = str_repeat("\x42", 16);
        $document = new PdfDocument(
            objects: $registry,
            version: PdfVersion::PDF_1_7,
            catalog: $catalogRef,
            info: null,
            documentId: $documentId,
        );
        $output = new PdfMemoryOutput();
        $serializer = new PdfDocumentSerializer($output);

        // Act
        $serializer->writeDocument($document);

        // Assert
        $hex = strtoupper(bin2hex($documentId));
        self::assertStringContainsString("/ID [<{$hex}> <{$hex}>]", $output->getContent());
    }

    #[Test]
    public function writeDocumentIncludesEncryptReferenceInTrailer(): void
    {
        // Arrange
        $registry = new PdfObjectRegistry();
        $catalogRef = $registry->register(new PdfDictionary(['Type' => new PdfName('Catalog')]));
        $encryptRef = $registry->register(new PdfDictionary(['Filter' => new PdfName('Standard')]));
        $document = new PdfDocument(
            objects: $registry,
            version: PdfVersion::PDF_1_7,
            catalog: $catalogRef,
            info: null,
            encryptDict: $encryptRef,
        );
        $output = new PdfMemoryOutput();
        $serializer = new PdfDocumentSerializer($output);

        // Act
        $serializer->writeDocument($document);

        // Assert
        self::assertStringContainsString(
            sprintf('/Encrypt %d %d R', $encryptRef->getObjectNumber(), $encryptRef->getGenerationNumber()),
            $output->getContent(),
        );
    }

    #[Test]
    public function writeDocumentCompressesStreamWhenCompressionEnabled(): void
    {
        // Arrange
        $registry = new PdfObjectRegistry();
        $catalogRef = $registry->register(new PdfDictionary(['Type' => new PdfName('Catalog')]));
        $registry->register(new PdfStream(
            new PdfDictionary(),
            new PdfRawStreamData('uncompressed content'),
        ));
        $document = new PdfDocument(
            objects: $registry,
            version: PdfVersion::PDF_1_7,
            catalog: $catalogRef,
            info: null,
            compressionEnabled: true,
        );
        $output = new PdfMemoryOutput();
        $serializer = new PdfDocumentSerializer($output);

        // Act
        $serializer->writeDocument($document);

        // Assert — compression adds the FlateDecode filter
        self::assertStringContainsString('/Filter /FlateDecode', $output->getContent());
    }

    #[Test]
    public function writeDocumentDoesNotCompressStreamThatAlreadyHasFilter(): void
    {
        // Arrange
        $registry = new PdfObjectRegistry();
        $catalogRef = $registry->register(new PdfDictionary(['Type' => new PdfName('Catalog')]));
        $registry->register(new PdfStream(
            new PdfDictionary(['Filter' => new PdfName('DCTDecode')]),
            new PdfRawStreamData("\xFF\xD8jpeg"),
        ));
        $document = new PdfDocument(
            objects: $registry,
            version: PdfVersion::PDF_1_7,
            catalog: $catalogRef,
            info: null,
            compressionEnabled: true,
        );
        $output = new PdfMemoryOutput();
        $serializer = new PdfDocumentSerializer($output);

        // Act
        $serializer->writeDocument($document);

        // Assert — original filter preserved, no FlateDecode added
        self::assertStringContainsString('/Filter /DCTDecode', $output->getContent());
        self::assertStringNotContainsString('FlateDecode', $output->getContent());
    }

    #[Test]
    public function writeDocumentDoesNotCompressMetadataStream(): void
    {
        // Arrange
        $registry = new PdfObjectRegistry();
        $catalogRef = $registry->register(new PdfDictionary(['Type' => new PdfName('Catalog')]));
        $registry->register(new PdfStream(
            new PdfDictionary(['Type' => new PdfName('Metadata')]),
            new PdfRawStreamData('<?xpacket?>'),
        ));
        $document = new PdfDocument(
            objects: $registry,
            version: PdfVersion::PDF_1_7,
            catalog: $catalogRef,
            info: null,
            compressionEnabled: true,
        );
        $output = new PdfMemoryOutput();
        $serializer = new PdfDocumentSerializer($output);

        // Act
        $serializer->writeDocument($document);

        // Assert — metadata streams must not be compressed so viewers can read XMP without decompressing
        self::assertStringNotContainsString('FlateDecode', $output->getContent());
    }

    #[Test]
    public function writeDocumentEncryptsStringObjects(): void
    {
        // Arrange
        $registry = new PdfObjectRegistry();
        $catalogRef = $registry->register(new PdfDictionary(['Type' => new PdfName('Catalog')]));
        $registry->register(new PdfDictionary(['Title' => new PdfString('Secret Title')]));
        $context = new PdfEncryptionContext(str_repeat('k', 16));
        $document = new PdfDocument(
            objects: $registry,
            version: PdfVersion::PDF_1_7,
            catalog: $catalogRef,
            info: null,
            encryptionContext: $context,
        );
        $output = new PdfMemoryOutput();
        $serializer = new PdfDocumentSerializer($output);

        // Act
        $serializer->writeDocument($document);

        // Assert — plain-text literal string must not appear; encrypted hex form must be present
        self::assertStringNotContainsString('(Secret Title)', $output->getContent());
        self::assertMatchesRegularExpression('/<[0-9A-F]+>/', $output->getContent());
    }

    #[Test]
    public function writeDocumentEncryptsDateObjects(): void
    {
        // Arrange
        $registry = new PdfObjectRegistry();
        $catalogRef = $registry->register(new PdfDictionary(['Type' => new PdfName('Catalog')]));
        $registry->register(new PdfDictionary([
            'CreationDate' => new PdfDate(new DateTimeImmutable('2023-01-01', new DateTimeZone('UTC'))),
        ]));
        $context = new PdfEncryptionContext(str_repeat('k', 16));
        $document = new PdfDocument(
            objects: $registry,
            version: PdfVersion::PDF_1_7,
            catalog: $catalogRef,
            info: null,
            encryptionContext: $context,
        );
        $output = new PdfMemoryOutput();
        $serializer = new PdfDocumentSerializer($output);

        // Act
        $serializer->writeDocument($document);

        // Assert — the plain-text date literal must not appear; encrypted hex form must be present
        self::assertStringNotContainsString('(D:2023', $output->getContent());
        self::assertMatchesRegularExpression('/<[0-9A-F]+>/', $output->getContent());
    }

    #[Test]
    public function writeDocumentEncryptsHexStringObjects(): void
    {
        // Arrange
        $registry = new PdfObjectRegistry();
        $catalogRef = $registry->register(new PdfDictionary(['Type' => new PdfName('Catalog')]));
        $registry->register(new PdfDictionary(['ID' => new PdfHexString('binary')]));
        $context = new PdfEncryptionContext(str_repeat('k', 16));
        $document = new PdfDocument(
            objects: $registry,
            version: PdfVersion::PDF_1_7,
            catalog: $catalogRef,
            info: null,
            encryptionContext: $context,
        );
        $output = new PdfMemoryOutput();
        $serializer = new PdfDocumentSerializer($output);

        // Act
        $serializer->writeDocument($document);

        // Assert — the plain hex form must be replaced by the encrypted hex form
        $plainHex = strtoupper(bin2hex('binary'));
        self::assertStringNotContainsString("<{$plainHex}>", $output->getContent());
    }

    #[Test]
    public function writeDocumentEncryptsStreamContent(): void
    {
        // Arrange
        $registry = new PdfObjectRegistry();
        $catalogRef = $registry->register(new PdfDictionary(['Type' => new PdfName('Catalog')]));
        $registry->register(new PdfStream(
            new PdfDictionary(),
            new PdfRawStreamData('plain stream content'),
        ));
        $context = new PdfEncryptionContext(str_repeat('k', 16));
        $document = new PdfDocument(
            objects: $registry,
            version: PdfVersion::PDF_1_7,
            catalog: $catalogRef,
            info: null,
            encryptionContext: $context,
        );
        $output = new PdfMemoryOutput();
        $serializer = new PdfDocumentSerializer($output);

        // Act
        $serializer->writeDocument($document);

        // Assert — plain content must not appear verbatim in the encrypted document
        self::assertStringNotContainsString('plain stream content', $output->getContent());
    }

    /** @return array{\PhpPdf\Object\PdfObjectRegistry, \PhpPdf\Object\PdfIndirectReference} */
    private function buildCatalogRegistry(): array
    {
        $registry = new PdfObjectRegistry();
        $catalogRef = $registry->register(new PdfDictionary(['Type' => new PdfName('Catalog')]));

        return [$registry, $catalogRef];
    }
}
