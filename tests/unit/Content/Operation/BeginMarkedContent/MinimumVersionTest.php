<?php

declare(strict_types=1);

namespace PhpPdf\Content\Operation\BeginMarkedContent;

use PhpPdf\Content\Operation\BeginMarkedContent;
use PhpPdf\Object\PdfVersion;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(BeginMarkedContent::class)]
#[CoversMethod(BeginMarkedContent::class, 'minimumVersion')]
final class MinimumVersionTest extends TestCase
{
    #[Test]
    public function minimumVersionReturnsExpectedVersion(): void
    {
        // Arrange / Act
        $version = (new BeginMarkedContent('Tag'))->minimumVersion();

        // Assert
        self::assertSame(PdfVersion::PDF_1_2, $version);
    }
}
