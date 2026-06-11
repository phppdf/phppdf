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
#[CoversMethod(HtmlConverterConfig::class, 'setBaseFontSize')]
#[UsesClass(HtmlFontFamily::class)]
#[UsesClass(Type1FontMetrics::class)]
final class SetBaseFontSizeTest extends TestCase
{
    #[Test]
    public function storesBaseFontSize(): void
    {
        // Arrange
        $config = new HtmlConverterConfig();

        // Act
        $result = $config->setBaseFontSize(14.0);

        // Assert
        self::assertSame(14.0, $config->getBaseFontSize());
        self::assertSame($config, $result);
    }
}
