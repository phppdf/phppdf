<?php

declare(strict_types=1);

namespace PhpPdf\Content\Operation\BeginMarkedContentWithProperties;

use PhpPdf\Content\Operation\BeginMarkedContentWithProperties;
use PhpPdf\Object\PdfVersion;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(BeginMarkedContentWithProperties::class)]
#[CoversMethod(BeginMarkedContentWithProperties::class, 'minimumVersion')]
final class MinimumVersionTest extends TestCase
{
    #[Test]
    public function minimumVersionReturnsExpectedVersion(): void
    {
        // Arrange / Act
        $version = (new BeginMarkedContentWithProperties('Tag', 'Props'))->minimumVersion();

        // Assert
        self::assertSame(PdfVersion::PDF_1_2, $version);
    }
}
