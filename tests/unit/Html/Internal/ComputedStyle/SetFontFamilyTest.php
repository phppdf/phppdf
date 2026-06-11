<?php

declare(strict_types=1);

namespace PhpPdf\Html\Internal\ComputedStyle;

use PhpPdf\Html\Internal\ComputedStyle;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(ComputedStyle::class)]
#[CoversMethod(ComputedStyle::class, 'setFontFamily')]
final class SetFontFamilyTest extends TestCase
{
    #[Test]
    public function storesFontFamily(): void
    {
        // Arrange
        $style = new ComputedStyle('helvetica', 11.0);

        // Act
        $style->setFontFamily('times-roman');

        // Assert
        self::assertSame('times-roman', $style->getFontFamily());
    }
}
