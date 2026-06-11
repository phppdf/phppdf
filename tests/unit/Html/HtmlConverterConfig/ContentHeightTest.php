<?php

declare(strict_types=1);

namespace PhpPdf\Html\HtmlConverterConfig;

use PhpPdf\Font\Type1FontMetrics;
use PhpPdf\Html\HtmlConverterConfig;
use PhpPdf\Html\HtmlFontFamily;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(HtmlConverterConfig::class)]
#[CoversMethod(HtmlConverterConfig::class, 'contentHeight')]
#[UsesClass(HtmlFontFamily::class)]
#[UsesClass(Type1FontMetrics::class)]
final class ContentHeightTest extends TestCase
{
    #[Test]
    public function returnsPageHeightMinusTopAndBottomMargins(): void
    {
        // Arrange
        $config = new HtmlConverterConfig();
        $config->setPageHeight(842);
        $config->setMarginTop(50.0);
        $config->setMarginBottom(50.0);

        // Act
        $result = $config->contentHeight();

        // Assert
        self::assertSame(742.0, $result);
    }

    #[Test]
    public function returnsDefaultA4ContentHeight(): void
    {
        // Arrange / Act
        $config = new HtmlConverterConfig();

        // Assert
        self::assertSame(842 - 72.0 - 72.0, $config->contentHeight());
    }
}
