<?php

declare(strict_types=1);

namespace PhpPdf\Reader\PdfAnnotation;

use PhpPdf\Reader\PdfAnnotation;
use PhpPdf\Reader\PdfAnnotationType;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(PdfAnnotation::class)]
#[CoversMethod(PdfAnnotation::class, 'isMarkup')]
final class IsMarkupTest extends TestCase
{
    #[DataProvider('markupProvider')]
    #[Test]
    public function returnsCorrectValueForType(PdfAnnotationType $type, bool $expected): void
    {
        // Arrange
        $annotation = new PdfAnnotation($type, 0.0, 0.0, 100.0, 20.0);

        // Act
        $result = $annotation->isMarkup();

        // Assert
        self::assertSame($expected, $result);
    }

    /** @return array<string, array{\PhpPdf\Reader\PdfAnnotationType, bool}> */
    public static function markupProvider(): array
    {
        return [
            'Circle is not markup' => [PdfAnnotationType::Circle, false],
            'Highlight is markup' => [PdfAnnotationType::Highlight, true],
            'Link is not markup' => [PdfAnnotationType::Link, false],
            'Square is not markup' => [PdfAnnotationType::Square, false],
            'Squiggly is markup' => [PdfAnnotationType::Squiggly, true],
            'StrikeOut is markup' => [PdfAnnotationType::StrikeOut, true],
            'Text is not markup' => [PdfAnnotationType::Text, false],
            'Underline is markup' => [PdfAnnotationType::Underline, true],
            'Unknown is not markup' => [PdfAnnotationType::Unknown, false],
        ];
    }
}
