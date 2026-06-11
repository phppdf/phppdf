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
#[CoversMethod(TextBox::class, 'getMaxWidth')]
final class GetMaxWidthTest extends TestCase
{
    #[Test]
    public function getMaxWidthReturnsConfiguredValue(): void
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

        $box = TextBox::create('Hello', $metrics, fontSize: 10, maxWidth: 350);

        self::assertEqualsWithDelta(350.0, $box->getMaxWidth(), 0.001);
    }
}
