<?php

declare(strict_types=1);

namespace PhpPdf\Content\Operation\SetGlyphWidth;

use PhpPdf\Content\Operation\SetGlyphWidth;
use PhpPdf\Object\PdfVersion;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(SetGlyphWidth::class)]
#[CoversMethod(SetGlyphWidth::class, 'minimumVersion')]
final class MinimumVersionTest extends TestCase
{
    #[Test]
    public function minimumVersionReturnsExpectedVersion(): void
    {
        // Arrange / Act
        $version = (new SetGlyphWidth(100.0, 0.0))->minimumVersion();

        // Assert
        self::assertSame(PdfVersion::PDF_1_0, $version);
    }
}
