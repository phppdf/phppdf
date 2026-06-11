<?php

declare(strict_types=1);

namespace PhpPdf\Html\Internal\ComputedStyle;

use PhpPdf\Html\Internal\ComputedStyle;
use PhpPdf\Text\TextAlign;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(ComputedStyle::class)]
#[CoversMethod(ComputedStyle::class, 'setTextAlign')]
final class SetTextAlignTest extends TestCase
{
    #[Test]
    public function storesTextAlign(): void
    {
        // Arrange
        $style = new ComputedStyle('helvetica', 11.0);

        // Act
        $style->setTextAlign(TextAlign::Center);

        // Assert
        self::assertSame(TextAlign::Center, $style->getTextAlign());
    }
}
