<?php

declare(strict_types=1);

namespace PhpPdf\Content\Operation\SetHorizontalTextScaling;

use PhpPdf\Content\Operation\SetHorizontalTextScaling;
use PhpPdf\Object\PdfVersion;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(SetHorizontalTextScaling::class)]
#[CoversMethod(SetHorizontalTextScaling::class, 'minimumVersion')]
final class MinimumVersionTest extends TestCase
{
    #[Test]
    public function minimumVersionReturnsExpectedVersion(): void
    {
        // Arrange / Act
        $version = (new SetHorizontalTextScaling(100.0))->minimumVersion();

        // Assert
        self::assertSame(PdfVersion::PDF_1_0, $version);
    }
}
