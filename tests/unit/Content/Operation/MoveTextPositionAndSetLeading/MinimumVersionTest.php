<?php

declare(strict_types=1);

namespace PhpPdf\Content\Operation\MoveTextPositionAndSetLeading;

use PhpPdf\Content\Operation\MoveTextPositionAndSetLeading;
use PhpPdf\Object\PdfVersion;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(MoveTextPositionAndSetLeading::class)]
#[CoversMethod(MoveTextPositionAndSetLeading::class, 'minimumVersion')]
final class MinimumVersionTest extends TestCase
{
    #[Test]
    public function minimumVersionReturnsExpectedVersion(): void
    {
        // Arrange / Act
        $version = (new MoveTextPositionAndSetLeading(1.0, 2.0))->minimumVersion();

        // Assert
        self::assertSame(PdfVersion::PDF_1_0, $version);
    }
}
