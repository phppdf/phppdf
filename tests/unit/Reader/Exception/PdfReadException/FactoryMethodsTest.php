<?php

declare(strict_types=1);

namespace PhpPdf\Reader\Exception\PdfReadException;

use PhpPdf\Reader\Exception\PdfReadException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(PdfReadException::class)]
#[CoversMethod(PdfReadException::class, 'fileNotFound')]
#[CoversMethod(PdfReadException::class, 'cannotOpenFile')]
#[CoversMethod(PdfReadException::class, 'invalidHeader')]
#[CoversMethod(PdfReadException::class, 'invalidXRef')]
#[CoversMethod(PdfReadException::class, 'unexpectedToken')]
#[CoversMethod(PdfReadException::class, 'unexpectedEndOfFile')]
#[CoversMethod(PdfReadException::class, 'objectNotFound')]
#[CoversMethod(PdfReadException::class, 'streamDecodeFailed')]
#[CoversMethod(PdfReadException::class, 'xrefStreamNotSupported')]
#[CoversMethod(PdfReadException::class, 'pageIndexOutOfBounds')]
#[CoversMethod(PdfReadException::class, 'wrongPassword')]
#[CoversMethod(PdfReadException::class, 'encryptDictNotFound')]
#[CoversMethod(PdfReadException::class, 'unsupportedEncryption')]
final class FactoryMethodsTest extends TestCase
{
    #[Test]
    public function fileNotFoundContainsPath(): void
    {
        // Arrange / Act
        $exception = PdfReadException::fileNotFound('/foo/bar.pdf');

        // Assert
        self::assertStringContainsString('/foo/bar.pdf', $exception->getMessage());
    }

    #[Test]
    public function cannotOpenFileContainsPath(): void
    {
        // Arrange / Act
        $exception = PdfReadException::cannotOpenFile('/foo/bar.pdf');

        // Assert
        self::assertStringContainsString('/foo/bar.pdf', $exception->getMessage());
    }

    #[Test]
    public function invalidHeaderReturnsException(): void
    {
        // Arrange / Act
        $exception = PdfReadException::invalidHeader();

        // Assert
        self::assertInstanceOf(PdfReadException::class, $exception);
        self::assertNotEmpty($exception->getMessage());
    }

    #[Test]
    public function invalidXRefContainsReason(): void
    {
        // Arrange / Act
        $exception = PdfReadException::invalidXRef('test reason');

        // Assert
        self::assertStringContainsString('test reason', $exception->getMessage());
    }

    #[Test]
    public function unexpectedTokenContainsExpectedAndGot(): void
    {
        // Arrange / Act
        $exception = PdfReadException::unexpectedToken('integer', 'keyword');

        // Assert
        self::assertStringContainsString('integer', $exception->getMessage());
        self::assertStringContainsString('keyword', $exception->getMessage());
    }

    #[Test]
    public function unexpectedEndOfFileReturnsException(): void
    {
        // Arrange / Act
        $exception = PdfReadException::unexpectedEndOfFile();

        // Assert
        self::assertInstanceOf(PdfReadException::class, $exception);
        self::assertNotEmpty($exception->getMessage());
    }

    #[Test]
    public function objectNotFoundContainsObjectNumber(): void
    {
        // Arrange / Act
        $exception = PdfReadException::objectNotFound(42);

        // Assert
        self::assertStringContainsString('42', $exception->getMessage());
    }

    #[Test]
    public function streamDecodeFailedContainsFilterName(): void
    {
        // Arrange / Act
        $exception = PdfReadException::streamDecodeFailed('FlateDecode');

        // Assert
        self::assertStringContainsString('FlateDecode', $exception->getMessage());
    }

    #[Test]
    public function xrefStreamNotSupportedReturnsException(): void
    {
        // Arrange / Act
        $exception = PdfReadException::xrefStreamNotSupported();

        // Assert
        self::assertInstanceOf(PdfReadException::class, $exception);
        self::assertNotEmpty($exception->getMessage());
    }

    #[Test]
    public function pageIndexOutOfBoundsContainsIndexAndCount(): void
    {
        // Arrange / Act
        $exception = PdfReadException::pageIndexOutOfBounds(5, 3);

        // Assert
        self::assertStringContainsString('5', $exception->getMessage());
        self::assertStringContainsString('3', $exception->getMessage());
    }

    #[Test]
    public function wrongPasswordReturnsException(): void
    {
        // Arrange / Act
        $exception = PdfReadException::wrongPassword();

        // Assert
        self::assertInstanceOf(PdfReadException::class, $exception);
        self::assertNotEmpty($exception->getMessage());
    }

    #[Test]
    public function encryptDictNotFoundReturnsException(): void
    {
        // Arrange / Act
        $exception = PdfReadException::encryptDictNotFound();

        // Assert
        self::assertInstanceOf(PdfReadException::class, $exception);
        self::assertNotEmpty($exception->getMessage());
    }

    #[Test]
    public function unsupportedEncryptionContainsReason(): void
    {
        // Arrange / Act
        $exception = PdfReadException::unsupportedEncryption('V=2 not supported');

        // Assert
        self::assertStringContainsString('V=2 not supported', $exception->getMessage());
    }
}
