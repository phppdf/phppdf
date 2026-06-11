<?php

declare(strict_types=1);

namespace PhpPdf\Content\Operation\SetTextLeading;

use PhpPdf\Content\Operation\SetTextLeading;
use PhpPdf\Object\PdfVersion;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(SetTextLeading::class)]
#[CoversMethod(SetTextLeading::class, 'minimumVersion')]
final class MinimumVersionTest extends TestCase
{
    #[Test]
    public function minimumVersionReturnsExpectedVersion(): void
    {
        // Arrange / Act
        $version = (new SetTextLeading(14.0))->minimumVersion();

        // Assert
        self::assertSame(PdfVersion::PDF_1_0, $version);
    }
}
