<?php

declare(strict_types=1);

namespace PhpPdf\Text\TextSpan;

use PhpPdf\Font\FontMetrics;
use PhpPdf\Text\TextSpan;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(TextSpan::class)]
#[CoversMethod(TextSpan::class, 'create')]
final class CreateTest extends TestCase
{
    #[Test]
    public function createReturnsTextSpanInstance(): void
    {
        // Arrange
        $metrics = $this->makeMetrics();

        // Act
        $span = TextSpan::create('Hello', 'F1', 12.0, $metrics);

        // Assert
        self::assertInstanceOf(TextSpan::class, $span);
    }

    #[Test]
    public function createStoresAllArguments(): void
    {
        // Arrange
        $metrics = $this->makeMetrics();

        // Act
        $span = TextSpan::create('world', 'F2', 9.5, $metrics);

        // Assert
        self::assertSame('world', $span->getText());
        self::assertSame('F2', $span->getFontName());
        self::assertEqualsWithDelta(9.5, $span->getFontSize(), 0.001);
        self::assertSame($metrics, $span->getMetrics());
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
