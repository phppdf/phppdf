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
#[CoversMethod(HtmlConverterConfig::class, 'getFamilyIndex')]
#[UsesClass(HtmlFontFamily::class)]
#[UsesClass(Type1FontMetrics::class)]
final class GetFamilyIndexTest extends TestCase
{
    #[Test]
    public function returnsZeroForFirstRegisteredFamily(): void
    {
        // Arrange
        $config = new HtmlConverterConfig();

        // Act
        $result = $config->getFamilyIndex('helvetica');

        // Assert
        self::assertSame(0, $result);
    }

    #[Test]
    public function returnsCorrectIndexForSubsequentFamilies(): void
    {
        // Arrange
        $config = new HtmlConverterConfig();

        // Act
        $timesIndex = $config->getFamilyIndex('times-roman');
        $courierIndex = $config->getFamilyIndex('courier');

        // Assert
        self::assertSame(1, $timesIndex);
        self::assertSame(2, $courierIndex);
    }

    #[Test]
    public function returnsZeroForUnknownFamily(): void
    {
        // Arrange
        $config = new HtmlConverterConfig();

        // Act
        $result = $config->getFamilyIndex('unknown');

        // Assert
        self::assertSame(0, $result);
    }

    #[Test]
    public function returnsCorrectIndexForCustomRegisteredFamily(): void
    {
        // Arrange
        $config = new HtmlConverterConfig();
        $config->registerFontFamily('custom', HtmlFontFamily::type1('Courier'));

        // Act
        $result = $config->getFamilyIndex('custom');

        // Assert
        self::assertSame(3, $result);
    }
}
