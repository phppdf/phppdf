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
#[CoversMethod(HtmlConverterConfig::class, 'contentWidth')]
#[UsesClass(HtmlFontFamily::class)]
#[UsesClass(Type1FontMetrics::class)]
final class ContentWidthTest extends TestCase
{
    #[Test]
    public function returnsPageWidthMinusLeftAndRightMargins(): void
    {
        // Arrange
        $config = new HtmlConverterConfig();
        $config->setPageWidth(595);
        $config->setMarginLeft(50.0);
        $config->setMarginRight(50.0);

        // Act
        $result = $config->contentWidth();

        // Assert
        self::assertSame(495.0, $result);
    }

    #[Test]
    public function returnsDefaultA4ContentWidth(): void
    {
        // Arrange / Act
        $config = new HtmlConverterConfig();

        // Assert
        self::assertSame(595 - 72.0 - 72.0, $config->contentWidth());
    }
}
