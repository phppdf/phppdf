<?php

declare(strict_types=1);

namespace PhpPdf\Content\Operation\PaintShading;

use PhpPdf\Content\Operation\PaintShading;
use PhpPdf\Object\PdfVersion;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(PaintShading::class)]
#[CoversMethod(PaintShading::class, 'minimumVersion')]
final class MinimumVersionTest extends TestCase
{
    #[Test]
    public function minimumVersionReturnsExpectedVersion(): void
    {
        // Arrange / Act
        $version = (new PaintShading('Sh1'))->minimumVersion();

        // Assert
        self::assertSame(PdfVersion::PDF_1_3, $version);
    }
}
