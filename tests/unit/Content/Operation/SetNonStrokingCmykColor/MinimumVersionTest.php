<?php

declare(strict_types=1);

namespace PhpPdf\Content\Operation\SetNonStrokingCmykColor;

use PhpPdf\Content\Operation\SetNonStrokingCmykColor;
use PhpPdf\Object\PdfVersion;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(SetNonStrokingCmykColor::class)]
#[CoversMethod(SetNonStrokingCmykColor::class, 'minimumVersion')]
final class MinimumVersionTest extends TestCase
{
    #[Test]
    public function minimumVersionReturnsExpectedVersion(): void
    {
        // Arrange / Act
        $version = (new SetNonStrokingCmykColor(0.0, 0.0, 0.0, 1.0))->minimumVersion();

        // Assert
        self::assertSame(PdfVersion::PDF_1_0, $version);
    }
}
