<?php

declare(strict_types=1);

namespace PhpPdf\Html\Internal\HtmlTableRowData;

use PhpPdf\Html\Internal\HtmlTableRowData;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(HtmlTableRowData::class)]
final class ConstructTest extends TestCase
{
    #[Test]
    public function hasExpectedDefaults(): void
    {
        // Arrange / Act
        $data = new HtmlTableRowData();

        // Assert
        self::assertSame([], $data->getCells());
        self::assertNull($data->getBackgroundColor());
        self::assertFalse($data->isHeader());
    }
}
