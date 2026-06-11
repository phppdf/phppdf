<?php

declare(strict_types=1);

namespace PhpPdf\Content\Operation\SetStrokingCmykColor;

use PhpPdf\Content\Operation\SetStrokingCmykColor;
use PhpPdf\Object\PdfVersion;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(SetStrokingCmykColor::class)]
#[CoversMethod(SetStrokingCmykColor::class, 'minimumVersion')]
final class MinimumVersionTest extends TestCase
{
    #[Test]
    public function minimumVersionReturnsExpectedVersion(): void
    {
        // Arrange / Act
        $version = (new SetStrokingCmykColor(0.0, 0.0, 0.0, 1.0))->minimumVersion();

        // Assert
        self::assertSame(PdfVersion::PDF_1_0, $version);
    }
}
