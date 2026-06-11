<?php

declare(strict_types=1);

namespace PhpPdf\Content\Operation\AppendCubicBezierReplicateInitial;

use PhpPdf\Content\Operation\AppendCubicBezierReplicateInitial;
use PhpPdf\Object\PdfVersion;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(AppendCubicBezierReplicateInitial::class)]
#[CoversMethod(AppendCubicBezierReplicateInitial::class, 'minimumVersion')]
final class MinimumVersionTest extends TestCase
{
    #[Test]
    public function minimumVersionReturnsExpectedVersion(): void
    {
        // Arrange / Act
        $version = (new AppendCubicBezierReplicateInitial(1.0, 2.0, 3.0, 4.0))->minimumVersion();

        // Assert
        self::assertSame(PdfVersion::PDF_1_0, $version);
    }
}
