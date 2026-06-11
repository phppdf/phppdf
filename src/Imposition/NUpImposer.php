<?php

declare(strict_types=1);

namespace PhpPdf\Imposition;

use PhpPdf\Builder\PdfDocumentBuilder;
use PhpPdf\Builder\PdfPageBuilder;
use PhpPdf\Content\PdfContentStreamBuilder;
use PhpPdf\Document\PdfDocument;
use PhpPdf\Document\PdfDocumentInfo;
use PhpPdf\Reader\PdfReadDocument;

/**
 * Imposes the pages of a source PDF into an N-up layout on fewer output sheets.
 *
 * Each source page is imported as a Form XObject and placed in its cell with
 * uniform scaling (aspect ratio preserved, centred with letter-boxing). Source
 * pages are placed in reading order: left-to-right, top-to-bottom.
 *
 * If the source page count is not a multiple of pagesPerSheet(), the last output
 * sheet may have fewer than N pages; unfilled cells are left blank.
 *
 * Usage:
 *
 *   $config = NUpConfig::twoUp(842, 595); // A4 landscape, 2 cols × 1 row
 *   $imposer = new NUpImposer($readDocument, $config);
 *   $output = new PdfFileOutput('/tmp/out.pdf');
 *   (new PdfDocumentSerializer($output))->writeDocument($imposer->impose());
 */
final class NUpImposer
{
    public function __construct(private readonly PdfReadDocument $source, private readonly NUpConfig $config,)
    {
    }

    /**
     * Produces the imposed document.
     *
     * @param \PhpPdf\Document\PdfDocumentInfo|null $info Optional document metadata for the output PDF.
     */
    public function impose(?PdfDocumentInfo $info = null): PdfDocument
    {
        $pageCount = $this->source->getPageCount();
        $n = $this->config->pagesPerSheet();
        $cellW = $this->config->cellWidth();
        $cellH = $this->config->cellHeight();
        $config = $this->config;

        $builder = new PdfDocumentBuilder();

        if ($info !== null) {
            $builder->info($info);
        }

        for ($sheetStart = 0; $sheetStart < $pageCount; $sheetStart += $n) {
            // Collect the source pages for this sheet.
            /** @var list<\PhpPdf\Reader\PdfReadPage> $srcPages */
            $srcPages = [];

            for ($i = $sheetStart, $end = min($sheetStart + $n, $pageCount); $i < $end; $i++) {
                $srcPages[] = $this->source->getPage($i);
            }

            $builder->page(
                static function (PdfPageBuilder $page) use ($srcPages, $config, $cellW, $cellH): void {
                    $page->size($config->sheetWidth, $config->sheetHeight);

                    // Register each source page as an imported Form XObject.
                    foreach ($srcPages as $pos => $srcPage) {
                        $page->useImportedPage("P{$pos}", $srcPage);
                    }

                    $page->content(
                        static function (PdfContentStreamBuilder $s) use ($srcPages, $config, $cellW, $cellH): void {
                            foreach ($srcPages as $pos => $srcPage) {
                                [$mbX, $mbY, $mbW, $mbH] = $srcPage->getMediaBox();
                                $srcW = $mbW - $mbX;
                                $srcH = $mbH - $mbY;

                                // Uniform scale to fit the cell while preserving aspect ratio.
                                $scale = min($cellW / $srcW, $cellH / $srcH);

                                // Centre the scaled page within the cell.
                                [$cellX, $cellY] = $config->cellOrigin($pos);
                                $tx = $cellX + ($cellW - $scale * $srcW) / 2.0 - $scale * $mbX;
                                $ty = $cellY + ($cellH - $scale * $srcH) / 2.0 - $scale * $mbY;

                                $s->drawImportedPage("P{$pos}", x: $tx, y: $ty, scale: $scale);
                            }
                        },
                    );
                },
            );
        }

        return $builder->build();
    }
}
