<?php

declare(strict_types=1);

namespace PhpPdf\Content\Operation\SetTextRenderingMode;

use PhpPdf\Content\Operation\SetTextRenderingMode;
use PhpPdf\Object\PdfVersion;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(SetTextRenderingMode::class)]
#[CoversMethod(SetTextRenderingMode::class, 'minimumVersion')]
final class MinimumVersionTest extends TestCase
{
    #[Test]
    public function minimumVersionReturnsExpectedVersion(): void
    {
        // Arrange / Act
        $version = (new SetTextRenderingMode(0))->minimumVersion();

        // Assert
        self::assertSame(PdfVersion::PDF_1_0, $version);
    }
}
