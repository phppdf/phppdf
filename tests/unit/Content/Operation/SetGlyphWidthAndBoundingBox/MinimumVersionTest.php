<?php

declare(strict_types=1);

namespace PhpPdf\Content\Operation\SetGlyphWidthAndBoundingBox;

use PhpPdf\Content\Operation\SetGlyphWidthAndBoundingBox;
use PhpPdf\Object\PdfVersion;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(SetGlyphWidthAndBoundingBox::class)]
#[CoversMethod(SetGlyphWidthAndBoundingBox::class, 'minimumVersion')]
final class MinimumVersionTest extends TestCase
{
    #[Test]
    public function minimumVersionReturnsExpectedVersion(): void
    {
        // Arrange / Act
        $version = (new SetGlyphWidthAndBoundingBox(100.0, 0.0, 0.0, 0.0, 10.0, 10.0))->minimumVersion();

        // Assert
        self::assertSame(PdfVersion::PDF_1_0, $version);
    }
}
