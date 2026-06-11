<?php

declare(strict_types=1);

namespace PhpPdf\Content\BlendMode;

use PhpPdf\Content\BlendMode;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(BlendMode::class)]
final class CasesTest extends TestCase
{
    #[Test]
    public function enumHasExpectedCases(): void
    {
        // Arrange / Act
        $cases = BlendMode::cases();

        // Assert
        self::assertCount(16, $cases);
        self::assertSame('Normal', BlendMode::Normal->value);
        self::assertSame('Multiply', BlendMode::Multiply->value);
    }
}
