<?php

declare(strict_types=1);

namespace PhpPdf\Content\Operation\FillAndStrokeEvenOdd;

use PhpPdf\Content\Operation\FillAndStrokeEvenOdd;
use PhpPdf\Object\PdfVersion;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(FillAndStrokeEvenOdd::class)]
#[CoversMethod(FillAndStrokeEvenOdd::class, 'minimumVersion')]
final class MinimumVersionTest extends TestCase
{
    #[Test]
    public function minimumVersionReturnsExpectedVersion(): void
    {
        // Arrange / Act
        $version = (new FillAndStrokeEvenOdd())->minimumVersion();

        // Assert
        self::assertSame(PdfVersion::PDF_1_0, $version);
    }
}
