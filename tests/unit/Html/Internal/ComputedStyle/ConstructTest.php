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
#[CoversMethod(ComputedStyle::class, '__construct')]
final class ConstructTest extends TestCase
{
    #[Test]
    public function setsFontFamilyAndFontSize(): void
    {
        // Arrange / Act
        $style = new ComputedStyle('helvetica', 12.0);

        // Assert
        self::assertSame('helvetica', $style->getFontFamily());
        self::assertSame(12.0, $style->getFontSize());
    }

    #[Test]
    public function defaultsToNotBoldOrItalic(): void
    {
        // Arrange / Act
        $style = new ComputedStyle('helvetica', 11.0);

        // Assert
        self::assertFalse($style->isBold());
        self::assertFalse($style->isItalic());
    }

    #[Test]
    public function defaultsToBlackColor(): void
    {
        // Arrange / Act
        $style = new ComputedStyle('helvetica', 11.0);

        // Assert
        self::assertSame([0.0, 0.0, 0.0], $style->getColor());
    }

    #[Test]
    public function defaultsToLeftTextAlign(): void
    {
        // Arrange / Act
        $style = new ComputedStyle('helvetica', 11.0);

        // Assert
        self::assertSame(TextAlign::Left, $style->getTextAlign());
    }

    #[Test]
    public function defaultsToZeroLineHeight(): void
    {
        // Arrange / Act
        $style = new ComputedStyle('helvetica', 11.0);

        // Assert
        self::assertSame(0.0, $style->getLineHeight());
    }

    #[Test]
    public function defaultsToNullBackgroundColor(): void
    {
        // Arrange / Act
        $style = new ComputedStyle('helvetica', 11.0);

        // Assert
        self::assertNull($style->getBackgroundColor());
    }

    #[Test]
    public function defaultsToZeroMargins(): void
    {
        // Arrange / Act
        $style = new ComputedStyle('helvetica', 11.0);

        // Assert
        self::assertSame(0.0, $style->getMarginTop());
        self::assertSame(0.0, $style->getMarginBottom());
        self::assertSame(0.0, $style->getMarginLeft());
        self::assertSame(0.0, $style->getPaddingLeft());
    }
}
