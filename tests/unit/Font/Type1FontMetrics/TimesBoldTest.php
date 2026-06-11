<?php

declare(strict_types=1);

namespace Type1FontMetrics;

use PhpPdf\Font\Type1FontMetrics;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(Type1FontMetrics::class)]
#[CoversMethod(Type1FontMetrics::class, 'timesBold')]
final class TimesBoldTest extends TestCase
{
    #[Test]
    public function timesBoldReturnsInstance(): void
    {
        $metrics = Type1FontMetrics::timesBold();

        self::assertInstanceOf(Type1FontMetrics::class, $metrics);
        self::assertSame(250.0, $metrics->charWidth(32));
    }
}
