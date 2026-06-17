<?php

declare(strict_types=1);

namespace PhpPdf\Font\Type1FontMetrics;

use PhpPdf\Font\Type1FontMetrics;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(Type1FontMetrics::class)]
#[CoversMethod(Type1FontMetrics::class, 'helveticaOblique')]
final class HelveticaObliqueTest extends TestCase
{
    #[Test]
    public function helveticaObliqueReturnsInstance(): void
    {
        $metrics = Type1FontMetrics::helveticaOblique();

        self::assertInstanceOf(Type1FontMetrics::class, $metrics);
        self::assertSame(278.0, $metrics->charWidth(32));
    }
}
