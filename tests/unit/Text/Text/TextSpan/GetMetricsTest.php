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
#[CoversMethod(TextSpan::class, 'getMetrics')]
final class GetMetricsTest extends TestCase
{
    #[Test]
    public function getMetricsReturnsTheSameInstancePassedToCreate(): void
    {
        // Arrange
        $metrics = $this->makeMetrics();
        $span = TextSpan::create('text', 'F1', 10.0, $metrics);

        // Act
        $result = $span->getMetrics();

        // Assert
        self::assertSame($metrics, $result);
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
