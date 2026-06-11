<?php

declare(strict_types=1);

namespace PhpPdf\Content\Operation\RestoreGraphicsState;

use PhpPdf\Content\Operation\RestoreGraphicsState;
use PhpPdf\Object\PdfVersion;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(RestoreGraphicsState::class)]
#[CoversMethod(RestoreGraphicsState::class, 'minimumVersion')]
final class MinimumVersionTest extends TestCase
{
    #[Test]
    public function minimumVersionReturnsExpectedVersion(): void
    {
        // Arrange / Act
        $version = (new RestoreGraphicsState())->minimumVersion();

        // Assert
        self::assertSame(PdfVersion::PDF_1_0, $version);
    }
}
