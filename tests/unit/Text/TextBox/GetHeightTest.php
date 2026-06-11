<?php

declare(strict_types=1);

namespace PhpPdf\Text\TextBox;

use PhpPdf\Font\FontMetrics;
use PhpPdf\Text\TextBox;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(TextBox::class)]
#[CoversMethod(TextBox::class, 'getHeight')]
final class GetHeightTest extends TestCase
{
    private FontMetrics $metrics;

    #[Test]
    public function getHeightIsLineCountTimesLineHeight(): void
    {
        // Arrange — 2 lines, lineHeight=14
        $box = TextBox::create("a\nb", $this->metrics, fontSize: 10, maxWidth: 200, lineHeight: 14);

        // Act / Assert
        self::assertEqualsWithDelta(28.0, $box->getHeight(), 0.001);
    }

    #[Test]
    public function getHeightForEmptyTextIsOneLineHeight(): void
    {
        // An empty string produces one empty line (blank-line preservation),
        // so height = 1 × lineHeight.
        $box = TextBox::create('', $this->metrics, fontSize: 10, maxWidth: 200, lineHeight: 14);

        self::assertEqualsWithDelta(14.0, $box->getHeight(), 0.001);
    }

    protected function setUp(): void
    {
        $this->metrics = new class implements FontMetrics {
            public function charWidth(int $codePoint): float
            {
                return 500.0;
            }

            public function stringWidth(string $text): float
            {
                return mb_strlen($text, 'UTF-8') * 500.0;
            }
        };
    }
}
