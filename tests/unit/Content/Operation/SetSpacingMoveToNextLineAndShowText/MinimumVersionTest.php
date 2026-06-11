<?php

declare(strict_types=1);

namespace PhpPdf\Content\Operation\SetSpacingMoveToNextLineAndShowText;

use PhpPdf\Content\Operation\SetSpacingMoveToNextLineAndShowText;
use PhpPdf\Object\PdfVersion;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(SetSpacingMoveToNextLineAndShowText::class)]
#[CoversMethod(SetSpacingMoveToNextLineAndShowText::class, 'minimumVersion')]
final class MinimumVersionTest extends TestCase
{
    #[Test]
    public function minimumVersionReturnsExpectedVersion(): void
    {
        // Arrange / Act
        $version = (new SetSpacingMoveToNextLineAndShowText(2.0, 0.0, '(Hello)'))->minimumVersion();

        // Assert
        self::assertSame(PdfVersion::PDF_1_0, $version);
    }
}
