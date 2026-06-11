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
#[CoversMethod(TextSpan::class, 'getText')]
final class GetTextTest extends TestCase
{
    #[Test]
    public function getTextReturnsSuppliedText(): void
    {
        // Arrange
        $span = TextSpan::create('Invoice:', 'F1', 10.0, $this->makeMetrics());

        // Act
        $result = $span->getText();

        // Assert
        self::assertSame('Invoice:', $result);
    }

    #[Test]
    public function getTextReturnsEmptyString(): void
    {
        // Arrange
        $span = TextSpan::create('', 'F1', 10.0, $this->makeMetrics());

        // Act / Assert
        self::assertSame('', $span->getText());
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
