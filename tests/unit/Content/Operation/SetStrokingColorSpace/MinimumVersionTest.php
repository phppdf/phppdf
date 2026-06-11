<?php

declare(strict_types=1);

namespace PhpPdf\Content\Operation\SetStrokingColorSpace;

use PhpPdf\Content\Operation\SetStrokingColorSpace;
use PhpPdf\Object\PdfVersion;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(SetStrokingColorSpace::class)]
#[CoversMethod(SetStrokingColorSpace::class, 'minimumVersion')]
final class MinimumVersionTest extends TestCase
{
    #[Test]
    public function minimumVersionReturnsExpectedVersion(): void
    {
        // Arrange / Act
        $version = (new SetStrokingColorSpace('DeviceRGB'))->minimumVersion();

        // Assert
        self::assertSame(PdfVersion::PDF_1_2, $version);
    }
}
