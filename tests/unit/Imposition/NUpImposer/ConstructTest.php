<?php

declare(strict_types=1);

namespace PhpPdf\Imposition\NUpImposer;

use PhpPdf\Imposition\NUpConfig;
use PhpPdf\Imposition\NUpImposer;
use PhpPdf\Object\PdfDictionary;
use PhpPdf\Object\PdfVersion;
use PhpPdf\Reader\PdfLexer;
use PhpPdf\Reader\PdfReadDocument;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(NUpImposer::class)]
#[CoversMethod(NUpImposer::class, '__construct')]
#[UsesClass(NUpConfig::class)]
#[UsesClass(PdfDictionary::class)]
#[UsesClass(PdfLexer::class)]
#[UsesClass(PdfReadDocument::class)]
final class ConstructTest extends TestCase
{
    #[Test]
    public function constructCreatesInstance(): void
    {
        // Arrange
        $source = new PdfReadDocument(
            PdfLexer::fromString(''),
            [],
            new PdfDictionary([]),
            PdfVersion::PDF_1_4,
        );
        $config = new NUpConfig(2, 1, 842, 595);

        // Act
        $imposer = new NUpImposer($source, $config);

        // Assert
        self::assertInstanceOf(NUpImposer::class, $imposer);
    }
}
