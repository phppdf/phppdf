<?php

declare(strict_types=1);

namespace PhpPdf\Builder\PdfPageBuilder;

use PhpPdf\Builder\PdfPageBuilder;
use PhpPdf\Object\PdfDictionary;
use PhpPdf\Object\PdfVersion;
use PhpPdf\Reader\PdfLexer;
use PhpPdf\Reader\PdfReadDocument;
use PhpPdf\Reader\PdfReadPage;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(PdfPageBuilder::class)]
#[CoversMethod(PdfPageBuilder::class, 'useImportedPage')]
#[UsesClass(PdfDictionary::class)]
#[UsesClass(PdfLexer::class)]
#[UsesClass(PdfReadDocument::class)]
#[UsesClass(PdfReadPage::class)]
final class UseImportedPageTest extends TestCase
{
    #[Test]
    public function useImportedPageReturnsSelf(): void
    {
        $page = new PdfPageBuilder();
        $readPage = self::makeReadPage();

        $result = $page->useImportedPage('TPL', $readPage);

        self::assertSame($page, $result);
    }

    private static function makeReadPage(): PdfReadPage
    {
        $document = new PdfReadDocument(
            PdfLexer::fromString(''),
            [],
            new PdfDictionary([]),
            PdfVersion::PDF_1_4,
        );

        return new PdfReadPage(new PdfDictionary([]), $document);
    }
}
