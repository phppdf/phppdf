<?php

declare(strict_types=1);

namespace PhpPdf\Text\TextBox;

use PhpPdf\Font\FontMetrics;
use PhpPdf\Text\TextAlign;
use PhpPdf\Text\TextBox;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(TextBox::class)]
#[CoversMethod(TextBox::class, 'getAlign')]
final class GetAlignTest extends TestCase
{
    #[Test]
    public function getAlignReturnsDefaultLeft(): void
    {
        $metrics = new class implements FontMetrics {
            public function charWidth(int $codePoint): float
            {
                return 500.0;
            }

            public function stringWidth(string $text): float
            {
                return 0.0;
            }
        };

        $box = TextBox::create('Hello', $metrics, fontSize: 10, maxWidth: 200);

        self::assertSame(TextAlign::Left, $box->getAlign());
    }

    #[Test]
    public function getAlignReturnsConfiguredAlign(): void
    {
        $metrics = new class implements FontMetrics {
            public function charWidth(int $codePoint): float
            {
                return 500.0;
            }

            public function stringWidth(string $text): float
            {
                return 0.0;
            }
        };

        $box = TextBox::create('Hello', $metrics, fontSize: 10, maxWidth: 200, align: TextAlign::Center);

        self::assertSame(TextAlign::Center, $box->getAlign());
    }
}
