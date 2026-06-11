<?php

declare(strict_types=1);

namespace PhpPdf\Html\Internal\HtmlTableData;

use PhpPdf\Html\Internal\HtmlTableData;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(HtmlTableData::class)]
#[CoversMethod(HtmlTableData::class, 'setPaddingBottom')]
final class SetPaddingBottomTest extends TestCase
{
    #[Test]
    public function storesPaddingBottom(): void
    {
        // Arrange
        $table = new HtmlTableData();

        // Act
        $table->setPaddingBottom(5.0);

        // Assert
        self::assertSame(5.0, $table->getPaddingBottom());
    }
}
