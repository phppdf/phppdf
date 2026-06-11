<?php

declare(strict_types=1);

namespace PhpPdf\Content\Operation\SetNonStrokingColor;

use PhpPdf\Content\Operation\SetNonStrokingColor;
use PhpPdf\Object\PdfVersion;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(SetNonStrokingColor::class)]
#[CoversMethod(SetNonStrokingColor::class, 'minimumVersion')]
final class MinimumVersionTest extends TestCase
{
    #[Test]
    public function minimumVersionReturnsExpectedVersion(): void
    {
        // Arrange / Act
        $version = (new SetNonStrokingColor(0.5, 0.5))->minimumVersion();

        // Assert
        self::assertSame(PdfVersion::PDF_1_2, $version);
    }
}
