<?php

declare(strict_types=1);

namespace PhpPdf\Reader\PdfAnnotationType;

use PhpPdf\Reader\PdfAnnotationType;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(PdfAnnotationType::class)]
#[CoversMethod(PdfAnnotationType::class, 'fromPdfName')]
final class FromPdfNameTest extends TestCase
{
    #[Test]
    public function returnsUnknownForNull(): void
    {
        // Arrange / Act
        $result = PdfAnnotationType::fromPdfName(null);

        // Assert
        self::assertSame(PdfAnnotationType::Unknown, $result);
    }

    #[Test]
    public function returnsUnknownForUnrecognisedName(): void
    {
        // Arrange / Act
        $result = PdfAnnotationType::fromPdfName('Widget');

        // Assert
        self::assertSame(PdfAnnotationType::Unknown, $result);
    }

    #[DataProvider('knownNamesProvider')]
    #[Test]
    public function mapsKnownNameToCase(string $name, PdfAnnotationType $expected): void
    {
        // Arrange / Act
        $result = PdfAnnotationType::fromPdfName($name);

        // Assert
        self::assertSame($expected, $result);
    }

    /** @return array<string, array{string, \PhpPdf\Reader\PdfAnnotationType}> */
    public static function knownNamesProvider(): array
    {
        return [
            'Circle' => ['Circle', PdfAnnotationType::Circle],
            'Highlight' => ['Highlight', PdfAnnotationType::Highlight],
            'Link' => ['Link', PdfAnnotationType::Link],
            'Square' => ['Square', PdfAnnotationType::Square],
            'Squiggly' => ['Squiggly', PdfAnnotationType::Squiggly],
            'StrikeOut' => ['StrikeOut', PdfAnnotationType::StrikeOut],
            'Text' => ['Text', PdfAnnotationType::Text],
            'Underline' => ['Underline', PdfAnnotationType::Underline],
        ];
    }
}
