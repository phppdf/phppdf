<?php

declare(strict_types=1);

namespace PhpPdf\Content\PdfContentStreamBuilder;

use PhpPdf\Barcode\QrCode;
use PhpPdf\Color\Color;
use PhpPdf\Color\ColorType;
use PhpPdf\Content\Operation\AppendRectangle;
use PhpPdf\Content\Operation\FillPath;
use PhpPdf\Content\Operation\RestoreGraphicsState;
use PhpPdf\Content\Operation\SaveGraphicsState;
use PhpPdf\Content\Operation\SetNonStrokingGray;
use PhpPdf\Content\PdfContentStreamBuilder;
use PhpPdf\Object\PdfContentStream;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(PdfContentStreamBuilder::class)]
#[CoversMethod(PdfContentStreamBuilder::class, 'drawQrCode')]
#[UsesClass(PdfContentStream::class)]
#[UsesClass(AppendRectangle::class)]
#[UsesClass(Color::class)]
#[UsesClass(ColorType::class)]
#[UsesClass(FillPath::class)]
#[UsesClass(QrCode::class)]
#[UsesClass(RestoreGraphicsState::class)]
#[UsesClass(SaveGraphicsState::class)]
#[UsesClass(SetNonStrokingGray::class)]
final class DrawQrCodeTest extends TestCase
{
    #[Test]
    public function drawQrCodeReturnsSelf(): void
    {
        $builder = new PdfContentStreamBuilder();
        $qr = QrCode::encode('Hello');
        self::assertSame($builder, $builder->drawQrCode($qr, x: 72.0, y: 600.0, moduleSize: 2.0));
    }

    #[Test]
    public function drawQrCodeAddsBackgroundAndDarkModules(): void
    {
        $builder = new PdfContentStreamBuilder();
        $qr = QrCode::encode('Hi');

        $builder->drawQrCode($qr, x: 0.0, y: 0.0, moduleSize: 2.0, quietZone: 4);
        $ops = $builder->build()->getOperations();
        $types = array_map(static fn ($op) => $op::class, $ops);

        // Always has white background block + dark-module block.
        self::assertContains(SaveGraphicsState::class, $types);
        self::assertContains(SetNonStrokingGray::class, $types);
        self::assertContains(AppendRectangle::class, $types);
        self::assertContains(FillPath::class, $types);
        self::assertContains(RestoreGraphicsState::class, $types);
    }

    #[Test]
    public function drawQrCodeWithCustomQuietZone(): void
    {
        $builder = new PdfContentStreamBuilder();
        $qr = QrCode::encode('A');

        $builder->drawQrCode($qr, x: 10.0, y: 10.0, moduleSize: 3.0, quietZone: 2);
        $ops = $builder->build()->getOperations();
        self::assertGreaterThan(4, count($ops));
    }
}
