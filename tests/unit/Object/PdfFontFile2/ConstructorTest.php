<?php

declare(strict_types=1);

namespace PhpPdf\Object\PdfFontFile2;

use PhpPdf\Object\PdfDictionary;
use PhpPdf\Object\PdfFontFile2;
use PhpPdf\Object\PdfInteger;
use PhpPdf\Object\PdfName;
use PhpPdf\Object\PdfRawStreamData;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

use function gzcompress;

#[CoversClass(PdfFontFile2::class)]
#[CoversMethod(PdfFontFile2::class, '__construct')]
#[UsesClass(PdfDictionary::class)]
#[UsesClass(PdfInteger::class)]
#[UsesClass(PdfName::class)]
#[UsesClass(PdfRawStreamData::class)]
final class ConstructorTest extends TestCase
{
    #[Test]
    public function constructorCompressesFontBinaryAndSetsLength1(): void
    {
        // Arrange
        $fontBinary = 'fake font binary';

        // Act
        $fontFile = new PdfFontFile2($fontBinary);

        // Assert
        $dictionary = $fontFile->getDictionary();
        self::assertEquals(new PdfName('FlateDecode'), $dictionary->get('Filter'));
        self::assertEquals(new PdfInteger(strlen($fontBinary)), $dictionary->get('Length1'));

        $data = $fontFile->getData();
        self::assertInstanceOf(PdfRawStreamData::class, $data);
        self::assertSame(gzcompress($fontBinary), $data->getData());
    }
}
