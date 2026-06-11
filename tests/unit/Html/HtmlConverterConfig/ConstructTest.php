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
#[CoversMethod(HtmlConverterConfig::class, '__construct')]
#[UsesClass(HtmlFontFamily::class)]
#[UsesClass(Type1FontMetrics::class)]
final class ConstructTest extends TestCase
{
    #[Test]
    public function setsA4PageDimensions(): void
    {
        // Arrange / Act
        $config = new HtmlConverterConfig();

        // Assert
        self::assertSame(595, $config->getPageWidth());
        self::assertSame(842, $config->getPageHeight());
    }

    #[Test]
    public function setsDefaultMargins(): void
    {
        // Arrange / Act
        $config = new HtmlConverterConfig();

        // Assert
        self::assertSame(72.0, $config->getMarginTop());
        self::assertSame(72.0, $config->getMarginRight());
        self::assertSame(72.0, $config->getMarginBottom());
        self::assertSame(72.0, $config->getMarginLeft());
    }

    #[Test]
    public function setsDefaultTypography(): void
    {
        // Arrange / Act
        $config = new HtmlConverterConfig();

        // Assert
        self::assertSame(11.0, $config->getBaseFontSize());
        self::assertSame(1.4, $config->getLineHeightMultiplier());
    }

    #[Test]
    public function preRegistersHelveticaFamily(): void
    {
        // Arrange / Act
        $config = new HtmlConverterConfig();

        // Assert
        self::assertNotNull($config->resolveFontFamilyName('helvetica'));
        self::assertNotNull($config->resolveFontFamilyName('arial'));
        self::assertNotNull($config->resolveFontFamilyName('sans-serif'));
    }

    #[Test]
    public function preRegistersTimesFamily(): void
    {
        // Arrange / Act
        $config = new HtmlConverterConfig();

        // Assert
        self::assertNotNull($config->resolveFontFamilyName('times-roman'));
        self::assertNotNull($config->resolveFontFamilyName('times'));
        self::assertNotNull($config->resolveFontFamilyName('serif'));
    }

    #[Test]
    public function preRegistersCourierFamily(): void
    {
        // Arrange / Act
        $config = new HtmlConverterConfig();

        // Assert
        self::assertNotNull($config->resolveFontFamilyName('courier'));
        self::assertNotNull($config->resolveFontFamilyName('monospace'));
    }

    #[Test]
    public function defaultFontFamilyIsHelvetica(): void
    {
        // Arrange / Act
        $config = new HtmlConverterConfig();

        // Assert
        self::assertSame('helvetica', $config->getDefaultFontFamily());
    }
}
