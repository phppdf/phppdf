<?php

declare(strict_types=1);

namespace PhpPdf\Content\Operation\DefineMarkedContentPoint;

use PhpPdf\Content\Operation\DefineMarkedContentPoint;
use PhpPdf\Object\PdfVersion;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(DefineMarkedContentPoint::class)]
#[CoversMethod(DefineMarkedContentPoint::class, 'minimumVersion')]
final class MinimumVersionTest extends TestCase
{
    #[Test]
    public function minimumVersionReturnsExpectedVersion(): void
    {
        // Arrange / Act
        $version = (new DefineMarkedContentPoint('Tag'))->minimumVersion();

        // Assert
        self::assertSame(PdfVersion::PDF_1_2, $version);
    }
}
