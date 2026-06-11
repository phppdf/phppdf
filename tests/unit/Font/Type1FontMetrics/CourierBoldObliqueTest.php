<?php

declare(strict_types=1);

namespace Type1FontMetrics;

use PhpPdf\Font\Type1FontMetrics;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(Type1FontMetrics::class)]
#[CoversMethod(Type1FontMetrics::class, 'courierBoldOblique')]
final class CourierBoldObliqueTest extends TestCase
{
    #[Test]
    public function courierBoldObliqueReturnsInstance(): void
    {
        $metrics = Type1FontMetrics::courierBoldOblique();

        self::assertInstanceOf(Type1FontMetrics::class, $metrics);
        self::assertSame(600.0, $metrics->charWidth(65));
    }
}
