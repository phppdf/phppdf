<?php

declare(strict_types=1);

namespace PhpPdf\Html\Internal\HtmlTableData;

use PhpPdf\Html\Internal\HtmlTableData;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(HtmlTableData::class)]
#[CoversMethod(HtmlTableData::class, 'setPaddingRight')]
final class SetPaddingRightTest extends TestCase
{
    #[Test]
    public function storesPaddingRight(): void
    {
        // Arrange
        $table = new HtmlTableData();

        // Act
        $table->setPaddingRight(10.0);

        // Assert
        self::assertSame(10.0, $table->getPaddingRight());
    }
}
