<?php

declare(strict_types=1);

namespace PhpPdf\Content\Operation\CloseFillAndStrokeEvenOdd;

use PhpPdf\Content\Operation\CloseFillAndStrokeEvenOdd;
use PhpPdf\Object\PdfVersion;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(CloseFillAndStrokeEvenOdd::class)]
#[CoversMethod(CloseFillAndStrokeEvenOdd::class, 'minimumVersion')]
final class MinimumVersionTest extends TestCase
{
    #[Test]
    public function minimumVersionReturnsExpectedVersion(): void
    {
        // Arrange / Act
        $version = (new CloseFillAndStrokeEvenOdd())->minimumVersion();

        // Assert
        self::assertSame(PdfVersion::PDF_1_0, $version);
    }
}
