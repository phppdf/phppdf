<?php

declare(strict_types=1);

namespace PhpPdf\Text\RichTextBox;

use PhpPdf\Font\FontMetrics;
use PhpPdf\Text\RichTextBox;
use PhpPdf\Text\TextSpan;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(RichTextBox::class)]
#[CoversMethod(RichTextBox::class, 'getHeight')]
#[UsesClass(TextSpan::class)]
final class GetHeightTest extends TestCase
{
    #[Test]
    public function getHeightIsLineCountTimesLineHeight(): void
    {
        // Arrange — 2 words wrap to 2 lines, lineHeight=14
        $spans = [TextSpan::create('aa bb', 'F1', 10.0, $this->makeMetrics())];
        $box = RichTextBox::create($spans, maxWidth: 20, lineHeight: 14.0);

        // Act
        $result = $box->getHeight();

        // Assert
        self::assertEqualsWithDelta(28.0, $result, 0.001);
    }

    #[Test]
    public function getHeightForEmptySpansIsOneLineHeight(): void
    {
        // Arrange — empty input produces one empty line
        $box = RichTextBox::create([], maxWidth: 200, lineHeight: 12.0);

        // Act / Assert
        self::assertEqualsWithDelta(12.0, $box->getHeight(), 0.001);
    }

    private function makeMetrics(): FontMetrics
    {
        return new class implements FontMetrics {
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
