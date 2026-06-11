<?php

declare(strict_types=1);

namespace Type1FontMetrics;

use PhpPdf\Font\Type1FontMetrics;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(Type1FontMetrics::class)]
#[CoversMethod(Type1FontMetrics::class, 'helveticaBold')]
final class HelveticaBoldTest extends TestCase
{
    #[Test]
    public function helveticaBoldReturnsInstance(): void
    {
        $metrics = Type1FontMetrics::helveticaBold();

        self::assertInstanceOf(Type1FontMetrics::class, $metrics);
        // Space = 278 in Helvetica Bold
        self::assertSame(278.0, $metrics->charWidth(32));
    }
}
