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
#[CoversMethod(HtmlConverterConfig::class, 'setLineHeightMultiplier')]
#[UsesClass(HtmlFontFamily::class)]
#[UsesClass(Type1FontMetrics::class)]
final class SetLineHeightMultiplierTest extends TestCase
{
    #[Test]
    public function storesLineHeightMultiplier(): void
    {
        // Arrange
        $config = new HtmlConverterConfig();

        // Act
        $result = $config->setLineHeightMultiplier(1.6);

        // Assert
        self::assertSame(1.6, $config->getLineHeightMultiplier());
        self::assertSame($config, $result);
    }
}
