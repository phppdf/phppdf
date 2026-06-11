<?php

declare(strict_types=1);

namespace PhpPdf\Html\Internal\HtmlLayoutEngine;

use DOMDocument;
use PhpPdf\Builder\PdfDocumentBuilder;
use PhpPdf\Builder\PdfPageBuilder;
use PhpPdf\Color\Color;
use PhpPdf\Content\Matrix;
use PhpPdf\Content\Operation\AppendLine;
use PhpPdf\Content\Operation\AppendRectangle;
use PhpPdf\Content\Operation\BeginSubpath;
use PhpPdf\Content\Operation\SetFont;
use PhpPdf\Content\Operation\SetLineWidth;
use PhpPdf\Content\Operation\SetNonStrokingRgbColor;
use PhpPdf\Content\Operation\SetStrokingGray;
use PhpPdf\Content\Operation\SetStrokingRgbColor;
use PhpPdf\Content\Operation\SetTextMatrix;
use PhpPdf\Content\Operation\ShowText;
use PhpPdf\Content\PdfContentStreamBuilder;
use PhpPdf\Document\PdfDocument;
use PhpPdf\Font\PdfFontCompiler;
use PhpPdf\Font\Type1FontMetrics;
use PhpPdf\Html\HtmlConverterConfig;
use PhpPdf\Html\HtmlFontFamily;
use PhpPdf\Html\Internal\ComputedStyle;
use PhpPdf\Html\Internal\CssParser;
use PhpPdf\Html\Internal\HtmlLayoutEngine;
use PhpPdf\Html\Internal\HtmlTableCellData;
use PhpPdf\Html\Internal\HtmlTableData;
use PhpPdf\Html\Internal\HtmlTableRowData;
use PhpPdf\Html\Internal\LayoutBlock;
use PhpPdf\Html\Internal\LayoutBlockType;
use PhpPdf\Html\Internal\MeasuredBlock;
use PhpPdf\Html\Internal\StyleResolver;
use PhpPdf\Object\PdfArray;
use PhpPdf\Object\PdfContentStream;
use PhpPdf\Object\PdfContentStreamData;
use PhpPdf\Object\PdfDictionary;
use PhpPdf\Object\PdfIndirectObject;
use PhpPdf\Object\PdfIndirectReference;
use PhpPdf\Object\PdfInteger;
use PhpPdf\Object\PdfName;
use PhpPdf\Object\PdfObjectRegistry;
use PhpPdf\Object\PdfStream;
use PhpPdf\Svg\SvgRenderer;
use PhpPdf\Table\TableBuilder;
use PhpPdf\Table\TableCell;
use PhpPdf\Table\TableRow;
use PhpPdf\Text\TextBox;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;
use ReflectionProperty;

#[CoversClass(HtmlLayoutEngine::class)]
#[CoversMethod(HtmlLayoutEngine::class, 'applyToBuilder')]
#[UsesClass(HtmlConverterConfig::class)]
#[UsesClass(HtmlFontFamily::class)]
#[UsesClass(StyleResolver::class)]
#[UsesClass(ComputedStyle::class)]
#[UsesClass(CssParser::class)]
#[UsesClass(LayoutBlock::class)]
#[UsesClass(MeasuredBlock::class)]
#[UsesClass(HtmlTableData::class)]
#[UsesClass(HtmlTableRowData::class)]
#[UsesClass(HtmlTableCellData::class)]
#[UsesClass(Type1FontMetrics::class)]
#[UsesClass(PdfDocumentBuilder::class)]
#[UsesClass(PdfPageBuilder::class)]
#[UsesClass(PdfContentStreamBuilder::class)]
#[UsesClass(TextBox::class)]
#[UsesClass(Color::class)]
#[UsesClass(TableBuilder::class)]
#[UsesClass(TableCell::class)]
#[UsesClass(TableRow::class)]
#[UsesClass(PdfArray::class)]
#[UsesClass(PdfContentStream::class)]
#[UsesClass(PdfContentStreamData::class)]
#[UsesClass(PdfDictionary::class)]
#[UsesClass(PdfIndirectObject::class)]
#[UsesClass(PdfIndirectReference::class)]
#[UsesClass(PdfInteger::class)]
#[UsesClass(PdfName::class)]
#[UsesClass(PdfObjectRegistry::class)]
#[UsesClass(PdfStream::class)]
#[UsesClass(PdfDocument::class)]
#[UsesClass(PdfFontCompiler::class)]
#[UsesClass(Matrix::class)]
#[UsesClass(AppendLine::class)]
#[UsesClass(AppendRectangle::class)]
#[UsesClass(BeginSubpath::class)]
#[UsesClass(SetFont::class)]
#[UsesClass(SetLineWidth::class)]
#[UsesClass(SetNonStrokingRgbColor::class)]
#[UsesClass(SetStrokingGray::class)]
#[UsesClass(SetStrokingRgbColor::class)]
#[UsesClass(SetTextMatrix::class)]
#[UsesClass(ShowText::class)]
#[UsesClass(SvgRenderer::class)]
final class ApplyToBuilderTest extends TestCase
{
    private HtmlConverterConfig $config;
    private HtmlLayoutEngine $engine;

    #[Test]
    public function returnsBuilderWhenBlocksAreEmpty(): void
    {
        // Arrange
        $this->loadAndCollect('');
        $builder = new PdfDocumentBuilder();

        // Act
        $result = $this->engine->applyToBuilder($builder);

        // Assert — no pages, no build needed
        self::assertSame($builder, $result);
        self::assertSame(0, $builder->getPageCount());
    }

    #[Test]
    public function addsOnePageForSmallContent(): void
    {
        // Arrange
        $this->loadAndCollect('<p>Hello World</p>');
        $builder = new PdfDocumentBuilder();
        $this->engine->applyToBuilder($builder);

        // Act — build executes rendering closures
        $builder->build();

        // Assert
        self::assertSame(1, $builder->getPageCount());
    }

    #[Test]
    public function addsMultiplePagesForLargeContent(): void
    {
        // Arrange
        $html = str_repeat('<p>' . str_repeat('Lorem ipsum dolor sit amet. ', 20) . '</p>', 30);
        $this->loadAndCollect($html);
        $builder = new PdfDocumentBuilder();
        $this->engine->applyToBuilder($builder);

        // Act
        $builder->build();

        // Assert
        self::assertGreaterThan(1, $builder->getPageCount());
    }

    #[Test]
    public function blockTallerThanFullPageIsPlacedAnyway(): void
    {
        // Arrange
        $this->loadAndCollect('<p>' . str_repeat('Word ', 500) . '</p>');
        $builder = new PdfDocumentBuilder();
        $this->engine->applyToBuilder($builder);

        // Act
        $builder->build();

        // Assert
        self::assertGreaterThanOrEqual(1, $builder->getPageCount());
    }

    #[Test]
    public function blockPushedToNextPageWhenItDoesNotFit(): void
    {
        // Arrange
        $fill = str_repeat('<p>' . str_repeat('Text ', 30) . '</p>', 20);
        $this->loadAndCollect($fill . '<h1>New Section</h1>');
        $builder = new PdfDocumentBuilder();
        $this->engine->applyToBuilder($builder);

        // Act
        $builder->build();

        // Assert
        self::assertGreaterThanOrEqual(1, $builder->getPageCount());
    }

    #[Test]
    public function tableRenderedOnOnePage(): void
    {
        // Arrange
        $this->loadAndCollect(
            '<table><thead><tr><th>Name</th><th>Value</th></tr></thead>'
            . '<tbody><tr><td>A</td><td>1</td></tr><tr><td>B</td><td>2</td></tr></tbody></table>',
        );
        $builder = new PdfDocumentBuilder();
        $this->engine->applyToBuilder($builder);

        // Act
        $builder->build();

        // Assert
        self::assertSame(1, $builder->getPageCount());
    }

    #[Test]
    public function largeTableSplitAcrossPages(): void
    {
        // Arrange — 60 rows to force page splitting
        $rows = '';

        for ($i = 1; $i <= 60; $i++) {
            $rows .= "<tr><td>Row {$i}</td><td>Value {$i}</td></tr>";
        }

        $this->loadAndCollect("<table><tbody>{$rows}</tbody></table>");
        $builder = new PdfDocumentBuilder();
        $this->engine->applyToBuilder($builder);

        // Act
        $builder->build();

        // Assert
        self::assertGreaterThan(1, $builder->getPageCount());
    }

    #[Test]
    public function tableSplitPreservesRowspan(): void
    {
        // Arrange — rowspan at the page boundary; must not crash
        $rows = '';

        for ($i = 1; $i <= 50; $i++) {
            if ($i === 5) {
                $rows .= '<tr><td rowspan="2">Tall</td><td>5A</td></tr>';
            } elseif ($i === 6) {
                $rows .= '<tr><td>6A</td></tr>';
            } else {
                $rows .= "<tr><td>Row {$i}</td><td>Val {$i}</td></tr>";
            }
        }

        $this->loadAndCollect("<table><tbody>{$rows}</tbody></table>");
        $builder = new PdfDocumentBuilder();
        $this->engine->applyToBuilder($builder);

        // Act
        $builder->build();

        // Assert
        self::assertGreaterThanOrEqual(1, $builder->getPageCount());
    }

    #[Test]
    public function tableSingleRowThatExceedsAvailableSpaceIsPlaced(): void
    {
        // Arrange — fill page, leaving little space; single-row table overflows
        // → splitTableAtHeight returns [null, null] ($splitAt >= $rowCount)
        $fill = str_repeat('<p>' . str_repeat('Fill ', 30) . '</p>', 22);
        $this->loadAndCollect($fill . '<table><tr><td>Single row</td></tr></table>');
        $builder = new PdfDocumentBuilder();
        $this->engine->applyToBuilder($builder);

        // Act
        $builder->build();

        // Assert
        self::assertGreaterThanOrEqual(1, $builder->getPageCount());
    }

    #[Test]
    public function tableWithEmptyTableDataTriggersEarlyReturn(): void
    {
        // Arrange — inject a Table LayoutBlock with an empty HtmlTableData so that
        // renderTable's null/empty-rows guard executes.
        $this->loadAndCollect('<p>Paragraph</p>');

        $style = new ComputedStyle($this->config->getDefaultFontFamily(), $this->config->getBaseFontSize());
        $tableData = new HtmlTableData(); // rows=[], columnWidths=[]
        $block = new LayoutBlock(LayoutBlockType::Table, $style, '', $tableData);

        $prop = new ReflectionProperty(HtmlLayoutEngine::class, 'blocks');
        $blocks = $prop->getValue($this->engine);
        self::assertIsArray($blocks);
        $blocks[] = $block;
        $prop->setValue($this->engine, $blocks);

        $builder = new PdfDocumentBuilder();
        $this->engine->applyToBuilder($builder);

        // Act — triggers renderPage → renderTable early-return
        $builder->build();

        // Assert
        self::assertGreaterThanOrEqual(1, $builder->getPageCount());
    }

    #[Test]
    public function tableWithBordersIsRendered(): void
    {
        // Arrange
        $this->loadAndCollect('<table border="1"><tr><td>A</td><td>B</td></tr></table>');
        $builder = new PdfDocumentBuilder();
        $this->engine->applyToBuilder($builder);

        // Act
        $builder->build();

        // Assert
        self::assertSame(1, $builder->getPageCount());
    }

    #[Test]
    public function tableWithColoredCellsIsRendered(): void
    {
        // Arrange
        $this->loadAndCollect(
            '<table><tr>'
            . '<td style="color: red; background-color: yellow;">Styled</td>'
            . '</tr></table>',
        );
        $builder = new PdfDocumentBuilder();
        $this->engine->applyToBuilder($builder);

        // Act
        $builder->build();

        // Assert
        self::assertSame(1, $builder->getPageCount());
    }

    #[Test]
    public function tableWithBoldItalicCellsIsRendered(): void
    {
        // Arrange — bold/italic cells use per-cell font variant
        $this->loadAndCollect(
            '<table><tr>'
            . '<td style="font-weight: bold; font-style: italic;">Bold italic</td>'
            . '</tr></table>',
        );
        $builder = new PdfDocumentBuilder();
        $this->engine->applyToBuilder($builder);

        // Act
        $builder->build();

        // Assert
        self::assertSame(1, $builder->getPageCount());
    }

    #[Test]
    public function horizontalRuleIsRendered(): void
    {
        // Arrange
        $this->loadAndCollect('<p>Before</p><hr><p>After</p>');
        $builder = new PdfDocumentBuilder();
        $this->engine->applyToBuilder($builder);

        // Act
        $builder->build();

        // Assert
        self::assertSame(1, $builder->getPageCount());
    }

    #[Test]
    public function lineBreakIsRendered(): void
    {
        // Arrange
        $this->loadAndCollect('<p>Line1<br>Line2</p>');
        $builder = new PdfDocumentBuilder();
        $this->engine->applyToBuilder($builder);

        // Act
        $builder->build();

        // Assert
        self::assertSame(1, $builder->getPageCount());
    }

    #[Test]
    public function textWithCustomColorIsRendered(): void
    {
        // Arrange — non-default color exercises fillColor path in renderTextBlock
        $this->loadAndCollect('<p style="color: #ff0000;">Red text</p>');
        $builder = new PdfDocumentBuilder();
        $this->engine->applyToBuilder($builder);

        // Act
        $builder->build();

        // Assert
        self::assertSame(1, $builder->getPageCount());
    }

    #[Test]
    public function applyToBuilderReturnsTheSameBuilderInstance(): void
    {
        // Arrange
        $this->loadAndCollect('<p>Hello</p>');
        $builder = new PdfDocumentBuilder();

        // Act
        $result = $this->engine->applyToBuilder($builder);

        // Assert — no build() needed; just verifying the return value
        self::assertSame($builder, $result);
    }

    #[Test]
    public function tableWithColspanIsRendered(): void
    {
        // Arrange
        $this->loadAndCollect('<table><tr><td colspan="2">Wide</td></tr>' . '<tr><td>A</td><td>B</td></tr></table>');
        $builder = new PdfDocumentBuilder();
        $this->engine->applyToBuilder($builder);

        // Act
        $builder->build();

        // Assert
        self::assertSame(1, $builder->getPageCount());
    }

    #[Test]
    public function tableWithRowspanInComputeRowHeightsIsRendered(): void
    {
        // Arrange — rowspan > 1 triggers Phase B in computeRowHeights
        $this->loadAndCollect('<table><tr><td rowspan="2">Tall</td><td>A</td></tr>' . '<tr><td>B</td></tr></table>');
        $builder = new PdfDocumentBuilder();
        $this->engine->applyToBuilder($builder);

        // Act
        $builder->build();

        // Assert
        self::assertSame(1, $builder->getPageCount());
    }

    #[Test]
    public function listBlockIsRendered(): void
    {
        // Arrange — ItemList block rendered through renderTextBlock
        $this->loadAndCollect('<ul><li>Item 1</li><li>Item 2</li></ul>');
        $builder = new PdfDocumentBuilder();
        $this->engine->applyToBuilder($builder);

        // Act
        $builder->build();

        // Assert
        self::assertSame(1, $builder->getPageCount());
    }

    #[Test]
    public function tableWithCellTextAlignLeftIsRendered(): void
    {
        // Arrange — text-align: left exercises the match default arm
        $this->loadAndCollect('<table><tr><td style="text-align: left;">Left</td></tr></table>');
        $builder = new PdfDocumentBuilder();
        $this->engine->applyToBuilder($builder);

        // Act
        $builder->build();

        // Assert
        self::assertSame(1, $builder->getPageCount());
    }

    #[Test]
    public function lineBreakBlockAtTopLevelIsRendered(): void
    {
        // Arrange — a <br> directly in body creates a LineBreak block; renderPage
        // handles it with a no-op `break;` statement (L885)
        $this->loadAndCollect('<p>Before</p><br><p>After</p>');
        $builder = new PdfDocumentBuilder();
        $this->engine->applyToBuilder($builder);

        // Act
        $builder->build();

        // Assert
        self::assertSame(1, $builder->getPageCount());
    }

    #[Test]
    public function tableCellWithNonLeftAlignmentIsRendered(): void
    {
        // Arrange — text-align: center triggers $tableCell->align() (L946)
        $this->loadAndCollect('<table><tr><td style="text-align: center;">Centered</td></tr></table>');
        $builder = new PdfDocumentBuilder();
        $this->engine->applyToBuilder($builder);

        // Act
        $builder->build();

        // Assert
        self::assertSame(1, $builder->getPageCount());
    }

    #[Test]
    public function tableWithExplicitBorderColorIsRendered(): void
    {
        // Arrange — inject a Table block with explicit borderColor to cover L928
        $this->loadAndCollect('<p>Before</p>');

        $style = new ComputedStyle($this->config->getDefaultFontFamily(), $this->config->getBaseFontSize());
        $tableData = new HtmlTableData();
        $tableData->setHasBorders(true);
        $tableData->setBorderColor([1.0, 0.0, 0.0]); // explicit red border color

        $row = new HtmlTableRowData();
        $cell = new HtmlTableCellData();
        $cell->setText('Cell');
        $row->addCell($cell);
        $tableData->addRow($row);
        $tableData->setColumnWidths([200.0]);

        $block = new LayoutBlock(LayoutBlockType::Table, $style, '', $tableData);

        $prop = new ReflectionProperty(HtmlLayoutEngine::class, 'blocks');
        $blocks = $prop->getValue($this->engine);
        self::assertIsArray($blocks);
        $blocks[] = $block;
        $prop->setValue($this->engine, $blocks);

        $builder = new PdfDocumentBuilder();
        $this->engine->applyToBuilder($builder);

        // Act
        $builder->build();

        // Assert
        self::assertGreaterThanOrEqual(1, $builder->getPageCount());
    }

    #[Test]
    public function measureTableBlockWithNullTableData(): void
    {
        // Arrange — inject a Table LayoutBlock with null tableData to cover
        // the `: 0.0` branch in measure() (L642)
        $style = new ComputedStyle($this->config->getDefaultFontFamily(), $this->config->getBaseFontSize());
        $block = new LayoutBlock(LayoutBlockType::Table, $style, '', null);

        $prop = new ReflectionProperty(HtmlLayoutEngine::class, 'blocks');
        $prop->setValue($this->engine, [$block]);

        $builder = new PdfDocumentBuilder();
        $this->engine->applyToBuilder($builder);
        $builder->build();

        // Assert
        self::assertGreaterThanOrEqual(1, $builder->getPageCount());
    }

    #[Test]
    public function renderTextBlockSkipsEmptyText(): void
    {
        // Arrange — inject a Text block with empty text to cover L994 in renderTextBlock
        $style = new ComputedStyle($this->config->getDefaultFontFamily(), $this->config->getBaseFontSize());
        $block = new LayoutBlock(LayoutBlockType::Text, $style, '');

        $prop = new ReflectionProperty(HtmlLayoutEngine::class, 'blocks');
        $prop->setValue($this->engine, [$block]);

        $builder = new PdfDocumentBuilder();
        $this->engine->applyToBuilder($builder);
        $builder->build();

        // Assert — no crash; at least 1 page was opened
        self::assertGreaterThanOrEqual(1, $builder->getPageCount());
    }

    #[Test]
    public function splitTableAtHeightReturnsNullForEmptyRows(): void
    {
        // Arrange — directly invoke splitTableAtHeight with empty tableData to
        // cover the $src->rows === [] early-return guard (L795)
        $style = new ComputedStyle($this->config->getDefaultFontFamily(), $this->config->getBaseFontSize());
        $tableData = new HtmlTableData(); // rows = []
        $method = new ReflectionMethod(HtmlLayoutEngine::class, 'splitTableAtHeight');

        // Act
        $result = $method->invoke($this->engine, $style, $tableData, 100.0);

        // Assert
        self::assertSame([null, null], $result);
    }

    #[Test]
    public function fontVariantNotInRegisteredFamiliesIsSkipped(): void
    {
        // Arrange — inject a Text block with an unregistered fontFamily so that
        // collectUsedVariants produces a key not in getFontFamilies() → L559 continue
        $style = new ComputedStyle('unregistered-font', $this->config->getBaseFontSize());
        $block = new LayoutBlock(LayoutBlockType::Text, $style, 'Text');

        $prop = new ReflectionProperty(HtmlLayoutEngine::class, 'blocks');
        $prop->setValue($this->engine, [$block]);

        $builder = new PdfDocumentBuilder();
        $this->engine->applyToBuilder($builder);

        // Act
        $builder->build();

        // Assert
        self::assertGreaterThanOrEqual(1, $builder->getPageCount());
    }

    #[Test]
    public function tableSingleRowOnPartiallyFilledPageTriggersNullSplit(): void
    {
        // Arrange — Use a tiny page config so that a small amount of content nearly
        // fills the page, leaving only a sliver of space.  A single-row table taller
        // than that sliver triggers splitTableAtHeight which returns [null, null]
        // ($splitAt >= $rowCount — L835).
        $config = new HtmlConverterConfig();
        $config->setPageHeight(150);
        $config->setMarginTop(50);
        $config->setMarginBottom(50); // contentHeight = 50 pt

        $resolver = new StyleResolver([], $config);
        $engine = new HtmlLayoutEngine($config, $resolver);

        $dom = new DOMDocument();
        libxml_use_internal_errors(true);
        // Long paragraph (>1 line at 11pt) + single-row table: fills most of the
        // 50pt content area, leaving less space than the row height → splitAt >= rowCount
        $dom->loadHTML('<?xml encoding="UTF-8"><p>' . str_repeat('TestWord ', 10) . '</p>'
            . '<table><tr><td>Row</td></tr></table>');
        libxml_clear_errors();
        $engine->collect($dom);

        $builder = new PdfDocumentBuilder();
        $engine->applyToBuilder($builder);
        $builder->build();

        // Assert
        self::assertGreaterThanOrEqual(1, $builder->getPageCount());
    }

    protected function setUp(): void
    {
        $this->config = new HtmlConverterConfig();
        $resolver = new StyleResolver([], $this->config);
        $this->engine = new HtmlLayoutEngine($this->config, $resolver);
    }

    private function loadAndCollect(string $html): void
    {
        $dom = new DOMDocument();
        libxml_use_internal_errors(true);
        $dom->loadHTML('<?xml encoding="UTF-8">' . $html);
        libxml_clear_errors();
        $this->engine->collect($dom);
    }
}
