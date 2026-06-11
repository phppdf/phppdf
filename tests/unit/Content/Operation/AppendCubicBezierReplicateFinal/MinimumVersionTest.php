<?php

declare(strict_types=1);

namespace PhpPdf\Content\Operation\AppendCubicBezierReplicateFinal;

use PhpPdf\Content\Operation\AppendCubicBezierReplicateFinal;
use PhpPdf\Object\PdfVersion;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(AppendCubicBezierReplicateFinal::class)]
#[CoversMethod(AppendCubicBezierReplicateFinal::class, 'minimumVersion')]
final class MinimumVersionTest extends TestCase
{
    #[Test]
    public function minimumVersionReturnsExpectedVersion(): void
    {
        // Arrange / Act
        $version = (new AppendCubicBezierReplicateFinal(1.0, 2.0, 3.0, 4.0))->minimumVersion();

        // Assert
        self::assertSame(PdfVersion::PDF_1_0, $version);
    }
}
