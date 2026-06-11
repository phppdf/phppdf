<?php

declare(strict_types=1);

namespace PhpPdf\Html\HtmlConverterConfig;

use InvalidArgumentException;
use PhpPdf\Font\Type1FontMetrics;
use PhpPdf\Html\HtmlConverterConfig;
use PhpPdf\Html\HtmlFontFamily;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(HtmlConverterConfig::class)]
#[CoversMethod(HtmlConverterConfig::class, 'registerFontFamily')]
#[UsesClass(HtmlFontFamily::class)]
#[UsesClass(Type1FontMetrics::class)]
final class RegisterFontFamilyTest extends TestCase
{
    #[Test]
    public function registersNewFamilyUnderPrimaryName(): void
    {
        // Arrange
        $config = new HtmlConverterConfig();
        $family = HtmlFontFamily::type1('Courier');

        // Act
        $config->registerFontFamily('myfont', $family);

        // Assert
        self::assertSame('myfont', $config->resolveFontFamilyName('myfont'));
    }

    #[Test]
    public function registersAliasesForFamily(): void
    {
        // Arrange
        $config = new HtmlConverterConfig();
        $family = HtmlFontFamily::type1('Courier');

        // Act
        $config->registerFontFamily(['myfont', 'alias1', 'alias2'], $family);

        // Assert
        self::assertSame('myfont', $config->resolveFontFamilyName('alias1'));
        self::assertSame('myfont', $config->resolveFontFamilyName('alias2'));
    }

    #[Test]
    public function throwsWhenNoNamesProvided(): void
    {
        // Arrange
        $config = new HtmlConverterConfig();
        $family = HtmlFontFamily::type1('Courier');
        $this->expectException(InvalidArgumentException::class);

        // Act
        $config->registerFontFamily([], $family);
    }

    #[Test]
    public function throwsWhenOnlyBlankNamesProvided(): void
    {
        // Arrange
        $config = new HtmlConverterConfig();
        $family = HtmlFontFamily::type1('Courier');
        $this->expectException(InvalidArgumentException::class);

        // Act
        $config->registerFontFamily(['  ', ''], $family);
    }

    #[Test]
    public function normalizesNamesToLowercase(): void
    {
        // Arrange
        $config = new HtmlConverterConfig();
        $family = HtmlFontFamily::type1('Courier');

        // Act
        $config->registerFontFamily('MyFont', $family);

        // Assert
        self::assertSame('myfont', $config->resolveFontFamilyName('MYFONT'));
    }

    #[Test]
    public function acceptsSingleStringName(): void
    {
        // Arrange
        $config = new HtmlConverterConfig();
        $family = HtmlFontFamily::type1('Courier');

        // Act
        $config->registerFontFamily('singlename', $family);

        // Assert
        self::assertSame('singlename', $config->resolveFontFamilyName('singlename'));
    }

    #[Test]
    public function returnsSelffForFluentInterface(): void
    {
        // Arrange
        $config = new HtmlConverterConfig();
        $family = HtmlFontFamily::type1('Courier');

        // Act
        $result = $config->registerFontFamily('myfont', $family);

        // Assert
        self::assertSame($config, $result);
    }
}
