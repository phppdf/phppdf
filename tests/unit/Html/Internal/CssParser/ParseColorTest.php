<?php

declare(strict_types=1);

namespace PhpPdf\Html\Internal\CssParser;

use PhpPdf\Html\Internal\CssParser;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(CssParser::class)]
#[CoversMethod(CssParser::class, 'parseColor')]
final class ParseColorTest extends TestCase
{
    #[Test]
    public function parsesFullHexColor(): void
    {
        // Arrange / Act
        $result = CssParser::parseColor('#ff0000');

        // Assert
        self::assertEqualsWithDelta([1.0, 0.0, 0.0], $result, 0.001);
    }

    #[Test]
    public function parsesShorthandHexColor(): void
    {
        // Arrange / Act
        $result = CssParser::parseColor('#f00');

        // Assert
        self::assertEqualsWithDelta([1.0, 0.0, 0.0], $result, 0.001);
    }

    #[Test]
    public function parsesRgbFunction(): void
    {
        // Arrange / Act
        $result = CssParser::parseColor('rgb(255, 0, 128)');

        // Assert
        self::assertNotNull($result);
        self::assertEqualsWithDelta([1.0, 0.0, 128 / 255], $result, 0.001);
    }

    #[Test]
    public function parsesNamedColorBlack(): void
    {
        // Arrange / Act
        $result = CssParser::parseColor('black');

        // Assert
        self::assertSame([0.0, 0.0, 0.0], $result);
    }

    #[Test]
    public function parsesNamedColorWhite(): void
    {
        // Arrange / Act
        $result = CssParser::parseColor('white');

        // Assert
        self::assertSame([1.0, 1.0, 1.0], $result);
    }

    #[Test]
    public function parsesNamedColorRed(): void
    {
        // Arrange / Act
        $result = CssParser::parseColor('red');

        // Assert
        self::assertSame([1.0, 0.0, 0.0], $result);
    }

    #[Test]
    public function cyanAndAquaAreTheSame(): void
    {
        // Arrange / Act
        $cyan = CssParser::parseColor('cyan');
        $aqua = CssParser::parseColor('aqua');

        // Assert
        self::assertSame($cyan, $aqua);
    }

    #[Test]
    public function magentaAndFuchsiaAreTheSame(): void
    {
        // Arrange / Act
        $magenta = CssParser::parseColor('magenta');
        $fuchsia = CssParser::parseColor('fuchsia');

        // Assert
        self::assertSame($magenta, $fuchsia);
    }

    #[Test]
    public function grayAndGreyAreTheSame(): void
    {
        // Arrange / Act
        $gray = CssParser::parseColor('gray');
        $grey = CssParser::parseColor('grey');

        // Assert
        self::assertSame($gray, $grey);
    }

    #[Test]
    public function parsesNamedColorBlue(): void
    {
        // Arrange / Act
        $result = CssParser::parseColor('blue');

        // Assert
        self::assertSame([0.0, 0.0, 1.0], $result);
    }

    #[Test]
    public function parsesNamedColorLime(): void
    {
        // Arrange / Act
        $result = CssParser::parseColor('lime');

        // Assert
        self::assertSame([0.0, 1.0, 0.0], $result);
    }

    #[Test]
    public function parsesNamedColorGreen(): void
    {
        // Arrange / Act
        $result = CssParser::parseColor('green');

        // Assert
        self::assertSame([0.0, 0.502, 0.0], $result);
    }

    #[Test]
    public function parsesNamedColorYellow(): void
    {
        // Arrange / Act
        $result = CssParser::parseColor('yellow');

        // Assert
        self::assertSame([1.0, 1.0, 0.0], $result);
    }

    #[Test]
    public function parsesNamedColorOrange(): void
    {
        // Arrange / Act
        $result = CssParser::parseColor('orange');

        // Assert
        self::assertSame([1.0, 0.647, 0.0], $result);
    }

    #[Test]
    public function parsesNamedColorPurple(): void
    {
        // Arrange / Act
        $result = CssParser::parseColor('purple');

        // Assert
        self::assertSame([0.502, 0.0, 0.502], $result);
    }

    #[Test]
    public function parsesNamedColorPink(): void
    {
        // Arrange / Act
        $result = CssParser::parseColor('pink');

        // Assert
        self::assertSame([1.0, 0.753, 0.796], $result);
    }

    #[Test]
    public function parsesNamedColorDarkgray(): void
    {
        // Arrange / Act
        $darkgray = CssParser::parseColor('darkgray');
        $darkgrey = CssParser::parseColor('darkgrey');

        // Assert
        self::assertSame([0.663, 0.663, 0.663], $darkgray);
        self::assertSame($darkgray, $darkgrey);
    }

    #[Test]
    public function parsesNamedColorLightgray(): void
    {
        // Arrange / Act
        $lightgray = CssParser::parseColor('lightgray');
        $lightgrey = CssParser::parseColor('lightgrey');

        // Assert
        self::assertSame([0.827, 0.827, 0.827], $lightgray);
        self::assertSame($lightgray, $lightgrey);
    }

    #[Test]
    public function parsesNamedColorSilver(): void
    {
        // Arrange / Act
        $result = CssParser::parseColor('silver');

        // Assert
        self::assertSame([0.753, 0.753, 0.753], $result);
    }

    #[Test]
    public function parsesNamedColorNavy(): void
    {
        // Arrange / Act
        $result = CssParser::parseColor('navy');

        // Assert
        self::assertSame([0.0, 0.0, 0.502], $result);
    }

    #[Test]
    public function parsesNamedColorTeal(): void
    {
        // Arrange / Act
        $result = CssParser::parseColor('teal');

        // Assert
        self::assertSame([0.0, 0.502, 0.502], $result);
    }

    #[Test]
    public function parsesNamedColorMaroon(): void
    {
        // Arrange / Act
        $result = CssParser::parseColor('maroon');

        // Assert
        self::assertSame([0.502, 0.0, 0.0], $result);
    }

    #[Test]
    public function parsesNamedColorOlive(): void
    {
        // Arrange / Act
        $result = CssParser::parseColor('olive');

        // Assert
        self::assertSame([0.502, 0.502, 0.0], $result);
    }

    #[Test]
    public function parsesNamedColorCoral(): void
    {
        // Arrange / Act
        $result = CssParser::parseColor('coral');

        // Assert
        self::assertSame([1.0, 0.498, 0.314], $result);
    }

    #[Test]
    public function parsesNamedColorSalmon(): void
    {
        // Arrange / Act
        $result = CssParser::parseColor('salmon');

        // Assert
        self::assertSame([0.98, 0.502, 0.447], $result);
    }

    #[Test]
    public function parsesNamedColorGold(): void
    {
        // Arrange / Act
        $result = CssParser::parseColor('gold');

        // Assert
        self::assertSame([1.0, 0.843, 0.0], $result);
    }

    #[Test]
    public function parsesNamedColorIndigo(): void
    {
        // Arrange / Act
        $result = CssParser::parseColor('indigo');

        // Assert
        self::assertSame([0.294, 0.0, 0.51], $result);
    }

    #[Test]
    public function parsesNamedColorViolet(): void
    {
        // Arrange / Act
        $result = CssParser::parseColor('violet');

        // Assert
        self::assertSame([0.933, 0.51, 0.933], $result);
    }

    #[Test]
    public function parsesNamedColorBrown(): void
    {
        // Arrange / Act
        $result = CssParser::parseColor('brown');

        // Assert
        self::assertSame([0.647, 0.165, 0.165], $result);
    }

    #[Test]
    public function returnsNullForTransparent(): void
    {
        // Arrange / Act
        $result = CssParser::parseColor('transparent');

        // Assert
        self::assertNull($result);
    }

    #[Test]
    public function returnsNullForUnknownValue(): void
    {
        // Arrange / Act
        $result = CssParser::parseColor('not-a-color');

        // Assert
        self::assertNull($result);
    }

    #[Test]
    public function isCaseInsensitive(): void
    {
        // Arrange / Act
        $result = CssParser::parseColor('RED');

        // Assert
        self::assertSame([1.0, 0.0, 0.0], $result);
    }

    #[Test]
    public function parsesHexColorCaseInsensitively(): void
    {
        // Arrange / Act
        $result = CssParser::parseColor('#FF0000');

        // Assert
        self::assertEqualsWithDelta([1.0, 0.0, 0.0], $result, 0.001);
    }
}
