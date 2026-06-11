<?php

declare(strict_types=1);

namespace PhpPdf\Reader\PdfFormFieldType;

use PhpPdf\Reader\PdfFormFieldType;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(PdfFormFieldType::class)]
#[CoversMethod(PdfFormFieldType::class, 'fromPdfName')]
final class FromPdfNameTest extends TestCase
{
    #[Test]
    public function returnsUnknownForNull(): void
    {
        // Arrange / Act
        $result = PdfFormFieldType::fromPdfName(null);

        // Assert
        self::assertSame(PdfFormFieldType::Unknown, $result);
    }

    #[Test]
    public function returnsUnknownForUnrecognisedName(): void
    {
        // Arrange / Act
        $result = PdfFormFieldType::fromPdfName('Xx');

        // Assert
        self::assertSame(PdfFormFieldType::Unknown, $result);
    }

    #[DataProvider('knownNamesProvider')]
    #[Test]
    public function mapsKnownNameToCase(string $name, PdfFormFieldType $expected): void
    {
        // Arrange / Act
        $result = PdfFormFieldType::fromPdfName($name);

        // Assert
        self::assertSame($expected, $result);
    }

    /** @return array<string, array{string, \PhpPdf\Reader\PdfFormFieldType}> */
    public static function knownNamesProvider(): array
    {
        return [
            'Btn' => ['Btn', PdfFormFieldType::Button],
            'Ch' => ['Ch', PdfFormFieldType::Choice],
            'Sig' => ['Sig', PdfFormFieldType::Signature],
            'Tx' => ['Tx', PdfFormFieldType::Text],
        ];
    }
}
