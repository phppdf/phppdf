<?php

declare(strict_types=1);

namespace PhpPdf\Content\Operation\SetNonStrokingRgbColor;

use PhpPdf\Content\Operation\SetNonStrokingRgbColor;
use PhpPdf\Object\PdfVersion;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(SetNonStrokingRgbColor::class)]
#[CoversMethod(SetNonStrokingRgbColor::class, 'minimumVersion')]
final class MinimumVersionTest extends TestCase
{
    #[Test]
    public function minimumVersionReturnsExpectedVersion(): void
    {
        // Arrange / Act
        $version = (new SetNonStrokingRgbColor(1.0, 0.0, 0.0))->minimumVersion();

        // Assert
        self::assertSame(PdfVersion::PDF_1_0, $version);
    }
}
