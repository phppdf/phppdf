<?php

declare(strict_types=1);

namespace PhpPdf\Content\Operation\MoveToNextLineAndShowText;

use PhpPdf\Content\Operation\MoveToNextLineAndShowText;
use PhpPdf\Object\PdfVersion;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(MoveToNextLineAndShowText::class)]
#[CoversMethod(MoveToNextLineAndShowText::class, 'minimumVersion')]
final class MinimumVersionTest extends TestCase
{
    #[Test]
    public function minimumVersionReturnsExpectedVersion(): void
    {
        // Arrange / Act
        $version = (new MoveToNextLineAndShowText('(Hello)'))->minimumVersion();

        // Assert
        self::assertSame(PdfVersion::PDF_1_0, $version);
    }
}
