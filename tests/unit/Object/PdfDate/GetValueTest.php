<?php

declare(strict_types=1);

namespace PhpPdf\Object\PdfDate;

use DateTimeImmutable;
use PhpPdf\Object\PdfDate;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(PdfDate::class)]
#[CoversMethod(PdfDate::class, 'getValue')]
final class GetValueTest extends TestCase
{
    #[Test]
    public function getValueReturnsStoredDate(): void
    {
        // Arrange
        $date = new DateTimeImmutable('2024-01-01');

        // Act / Assert
        self::assertSame($date, (new PdfDate($date))->getValue());
    }
}
