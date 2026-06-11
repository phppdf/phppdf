<?php

declare(strict_types=1);

namespace PhpPdf\Html\Internal\HtmlTableData;

use PhpPdf\Html\Internal\HtmlTableData;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(HtmlTableData::class)]
final class ConstructTest extends TestCase
{
    #[Test]
    public function hasExpectedDefaults(): void
    {
        // Arrange / Act
        $data = new HtmlTableData();

        // Assert
        self::assertSame([], $data->getColumnWidths());
        self::assertFalse($data->hasBorders());
        self::assertNull($data->getBorderColor());
        self::assertSame(0.5, $data->getBorderWidth());
        self::assertSame(4.0, $data->getPaddingTop());
        self::assertSame(6.0, $data->getPaddingRight());
        self::assertSame(3.0, $data->getPaddingBottom());
        self::assertSame(6.0, $data->getPaddingLeft());
        self::assertSame([], $data->getRows());
    }
}
