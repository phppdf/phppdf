<?php

declare(strict_types=1);

namespace PhpPdf\Object\PdfNumberTree;

use PhpPdf\Object\PdfArray;
use PhpPdf\Object\PdfDictionary;
use PhpPdf\Object\PdfInteger;
use PhpPdf\Object\PdfNumberTree;
use PhpPdf\Object\PdfString;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(PdfNumberTree::class)]
#[CoversMethod(PdfNumberTree::class, '__construct')]
#[UsesClass(PdfArray::class)]
#[UsesClass(PdfDictionary::class)]
#[UsesClass(PdfInteger::class)]
#[UsesClass(PdfString::class)]
final class ConstructorTest extends TestCase
{
    #[Test]
    public function constructorCreatesEmptyTree(): void
    {
        // Arrange / Act
        $obj = new PdfNumberTree([]);

        // Assert
        self::assertInstanceOf(PdfDictionary::class, $obj);
    }

    #[Test]
    public function constructorCreatesTreeWithEntries(): void
    {
        // Arrange / Act
        $obj = new PdfNumberTree([0 => new PdfString('first'), 1 => new PdfString('second')]);

        // Assert
        self::assertInstanceOf(PdfDictionary::class, $obj);
    }
}
