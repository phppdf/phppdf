<?php

declare(strict_types=1);

namespace PhpPdf\Content\Operation\BeginCompatibilitySection;

use PhpPdf\Content\Operation\BeginCompatibilitySection;
use PhpPdf\Object\PdfVersion;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(BeginCompatibilitySection::class)]
#[CoversMethod(BeginCompatibilitySection::class, 'minimumVersion')]
final class MinimumVersionTest extends TestCase
{
    #[Test]
    public function minimumVersionReturnsExpectedVersion(): void
    {
        // Arrange / Act
        $version = (new BeginCompatibilitySection())->minimumVersion();

        // Assert
        self::assertSame(PdfVersion::PDF_1_1, $version);
    }
}
