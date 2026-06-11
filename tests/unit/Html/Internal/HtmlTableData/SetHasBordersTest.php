<?php

declare(strict_types=1);

namespace PhpPdf\Html\Internal\HtmlTableData;

use PhpPdf\Html\Internal\HtmlTableData;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(HtmlTableData::class)]
#[CoversMethod(HtmlTableData::class, 'setHasBorders')]
final class SetHasBordersTest extends TestCase
{
    #[Test]
    public function storesHasBorders(): void
    {
        // Arrange
        $table = new HtmlTableData();

        // Act
        $table->setHasBorders(true);

        // Assert
        self::assertTrue($table->hasBorders());
    }
}
