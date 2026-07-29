<?php

namespace App\Exports;

use Illuminate\Support\Facades\App;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;

abstract class BaseExportTemplate implements
    FromCollection,
    WithMapping,
    WithHeadings,
    WithStyles,
    WithColumnWidths,
    WithEvents
{
    protected $counter = 1;
    protected $isRTL;
    protected $logoPath;
    protected $logoHeight;
    protected $title;
    protected $description;

    public function __construct($locale = null, $title = null, $description = null)
    {
        $locale = $locale ?? app()->getLocale();
        app()->setLocale($locale);

        $this->isRTL       = $locale === 'ar';
        $this->title       = $title;
        $this->description = $description;
        $this->logoPath    = public_path('images/ksu-logo.png');
        $this->logoHeight  = 80;
    }

    abstract public function collection();
    abstract public function map($item): array;
    abstract public function headings(): array;
    abstract public function columnWidths(): array;

    /**
     * Get last column letter dynamically
     */
    protected function getLastColumnLetter(): string
    {
        return Coordinate::stringFromColumnIndex(count($this->headings()));
    }

    /**
     * styles() is required by WithStyles but we handle all styling in registerEvents().
     */
    public function styles(Worksheet $sheet)
    {
        return [];
    }

    // -------------------------------------------------------------------------
    // Border helpers
    // -------------------------------------------------------------------------

    /**
     * Apply borders to entire range - ALL borders (inside and outside)
     * This matches the image style
     */
    private function applyAllBorders(Worksheet $sheet, string $fromCell, string $toCell): void
    {
        $sheet->getStyle("{$fromCell}:{$toCell}")->applyFromArray([
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color'       => ['rgb' => '000000'], // Black border like in the image
                ],
            ],
        ]);
    }

    /**
     * Apply thicker outer border wrapping the entire content block
     * (like the image shows a thicker border around the table)
     */
    private function applyOuterBorder(Worksheet $sheet, string $fromCell, string $toCell): void
    {
        $sheet->getStyle("{$fromCell}:{$toCell}")->applyFromArray([
            'borders' => [
                'outline' => [
                    'borderStyle' => Border::BORDER_MEDIUM,
                    'color'       => ['rgb' => '000000'], // Black border like in the image
                ],
            ],
        ]);
    }

    // -------------------------------------------------------------------------
    // Events
    // -------------------------------------------------------------------------

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();

                // Reset counter
                $this->counter = 1;

                if ($this->isRTL) {
                    $sheet->setRightToLeft(true);
                }

                $headings  = $this->headings();
                $totalRows = $this->collection()->count();

                // ------------------------------------------------------------------
                // Column layout: data always starts at column A
                // ------------------------------------------------------------------
                $dataStartColIndex = 1;
                $dataEndColIndex   = count($headings);
                $dataStartCol      = 'A';
                $dataEndCol        = Coordinate::stringFromColumnIndex($dataEndColIndex);

                // Logo anchor: visually on the right side in both LTR and RTL
                $logoAnchorCol = $this->isRTL ? 'A' : $dataEndCol;

                // ------------------------------------------------------------------
                // Row layout
                // ------------------------------------------------------------------
                $currentRow  = 1;
                $contentFrom = 'A1'; // top-left corner of the entire bordered block

                // --- Title row ---
                if ($this->title) {
                    $sheet->mergeCells("A{$currentRow}:{$dataEndCol}{$currentRow}");
                    $sheet->setCellValue("A{$currentRow}", $this->title);
                    $sheet->getStyle("A{$currentRow}:{$dataEndCol}{$currentRow}")->applyFromArray([
                        'font'      => [
                            'bold'  => true,
                            'size'  => 14,
                            'color' => ['rgb' => '2F5496'],
                            'name'  => 'Calibri',
                        ],
                        'alignment' => [
                            'horizontal' => Alignment::HORIZONTAL_CENTER,
                            'vertical'   => Alignment::VERTICAL_CENTER,
                        ],
                    ]);

                    $sheet->getRowDimension($currentRow)->setRowHeight(70);

                    if (file_exists($this->logoPath)) {
                        $drawing = new Drawing();
                        $drawing->setName('Logo');
                        $drawing->setDescription('Logo');
                        $drawing->setPath($this->logoPath);
                        $drawing->setHeight($this->logoHeight);
                        $drawing->setCoordinates($logoAnchorCol . $currentRow);
                        $drawing->setOffsetX(5);
                        $drawing->setOffsetY(10);
                        $drawing->setWorksheet($sheet);
                    }

                    $currentRow++;
                }

                // --- Description row ---
                if ($this->description) {
                    $sheet->mergeCells("A{$currentRow}:{$dataEndCol}{$currentRow}");
                    $sheet->setCellValue("A{$currentRow}", $this->description);
                    $sheet->getStyle("A{$currentRow}:{$dataEndCol}{$currentRow}")->applyFromArray([
                        'font'      => [
                            'size'   => 10,
                            'italic' => true,
                            'color'  => ['rgb' => '666666'],
                            'name'   => 'Calibri',
                        ],
                        'alignment' => [
                            'horizontal' => Alignment::HORIZONTAL_CENTER,
                            'vertical'   => Alignment::VERTICAL_CENTER,
                        ],
                    ]);
                    $sheet->getRowDimension($currentRow)->setRowHeight(25);
                    $currentRow++;
                }

                // --- Header row ---
                $headerRow = $currentRow;
                $col       = $dataStartColIndex;
                foreach ($headings as $heading) {
                    $sheet->setCellValueByColumnAndRow($col, $headerRow, $heading);
                    $col++;
                }

                $sheet->getStyle("{$dataStartCol}{$headerRow}:{$dataEndCol}{$headerRow}")->applyFromArray([
                    'font'      => [
                        'bold'  => true,
                        'color' => ['rgb' => '2F5496'],
                        'size'  => 11,
                        'name'  => 'Calibri',
                    ],
                    'alignment' => [
                        'horizontal' => $this->isRTL
                            ? Alignment::HORIZONTAL_RIGHT
                            : Alignment::HORIZONTAL_CENTER,
                        'vertical'   => Alignment::VERTICAL_CENTER,
                    ],
                    'fill'      => [
                        'fillType' => Fill::FILL_SOLID,
                        'color'    => ['rgb' => 'E7E6E6'],
                    ],
                ]);
                $sheet->getRowDimension($headerRow)->setRowHeight(40);
                $currentRow++;

                // --- Data rows ---
                $dataFirstRow = $currentRow;
                foreach ($this->collection() as $item) {
                    $mappedData = $this->map($item);
                    $col        = $dataStartColIndex;
                    foreach ($mappedData as $value) {
                        $sheet->setCellValueByColumnAndRow($col, $currentRow, $value);
                        $col++;
                    }
                    $currentRow++;
                }
                $dataLastRow = $currentRow - 1;

                // --- Determine the last row that has content ---
                $lastContentRow = ($totalRows > 0) ? $dataLastRow : ($this->description ? $headerRow : ($this->title ? $headerRow : $headerRow));

                // --- Apply ALL borders (inside and outside) like in the image ---
                // This applies thin borders to every cell in the table area
                if ($totalRows > 0) {
                    // Apply borders to header + data rows
                    $this->applyAllBorders($sheet, "{$dataStartCol}{$headerRow}", "{$dataEndCol}{$dataLastRow}");
                    
                    // Apply borders to title and description rows as well if they exist
                    if ($this->title) {
                        $this->applyAllBorders($sheet, "{$dataStartCol}1", "{$dataEndCol}1");
                    }
                    if ($this->description) {
                        $titleRowCount = $this->title ? 2 : 1;
                        $descRow = $this->title ? 2 : 1;
                        $this->applyAllBorders($sheet, "{$dataStartCol}{$descRow}", "{$dataEndCol}{$descRow}");
                    }
                } else if ($totalRows === 0 && $headerRow) {
                    // If no data rows, still apply borders to header
                    $this->applyAllBorders($sheet, "{$dataStartCol}{$headerRow}", "{$dataEndCol}{$headerRow}");
                }

                // --- Apply thicker OUTER border wrapping everything (like the image) ---
                // Start from the first row that has content (row 1 if title exists, otherwise header row)
                $firstContentRow = $this->title ? 1 : ($this->description ? ($this->title ? 2 : 1) : $headerRow);
                $this->applyOuterBorder($sheet, "{$dataStartCol}{$firstContentRow}", "{$dataEndCol}{$lastContentRow}");

                // --- Data rows styling ---
                if ($totalRows > 0) {
                    $dataAlignment = $this->isRTL
                        ? Alignment::HORIZONTAL_RIGHT
                        : Alignment::HORIZONTAL_LEFT;

                    $sheet->getStyle("{$dataStartCol}{$dataFirstRow}:{$dataEndCol}{$dataLastRow}")->applyFromArray([
                        'font'      => [
                            'size' => 11,
                            'name' => 'Calibri',
                        ],
                        'alignment' => [
                            'vertical'   => Alignment::VERTICAL_CENTER,
                            'horizontal' => $dataAlignment,
                        ],
                    ]);

                    // Center the counter/# column (A)
                    $sheet->getStyle("{$dataStartCol}{$dataFirstRow}:{$dataStartCol}{$dataLastRow}")
                        ->getAlignment()
                        ->setHorizontal(Alignment::HORIZONTAL_CENTER);

                    // Alternating row colors: even rows get a subtle fill, odd rows stay plain
                    for ($row = $dataFirstRow; $row <= $dataLastRow; $row++) {
                        if ($row % 2 === 0) {
                            $sheet->getStyle("{$dataStartCol}{$row}:{$dataEndCol}{$row}")->applyFromArray([
                                'fill' => [
                                    'fillType' => Fill::FILL_SOLID,
                                    'color'    => ['rgb' => 'F8F9FA'],
                                ],
                            ]);
                        }
                    }
                }

                // --- Auto-size all data columns ---
                for ($c = $dataStartColIndex; $c <= $dataEndColIndex; $c++) {
                    $sheet->getColumnDimensionByColumn($c)->setAutoSize(true);
                }

                // --- Apply column widths from subclass ---
                $columnWidths = $this->columnWidths();
                if (!empty($columnWidths)) {
                    foreach ($columnWidths as $column => $width) {
                        $sheet->getColumnDimension($column)->setWidth($width);
                    }
                }

                // --- Freeze pane below header row ---
                $sheet->freezePane("{$dataStartCol}" . ($headerRow + 1));

                // --- Apply subclass custom styles ---
                $this->applyCustomStyles($sheet);
            },
        ];
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    protected function trans($key, $replace = [], $locale = null)
    {
        $locale         = $locale ?? ($this->isRTL ? 'ar' : 'en');
        $originalLocale = App::getLocale();
        App::setLocale($locale);

        $translation = __($key, $replace);

        if ($translation === $key) {
            $parts       = explode('.', $key);
            $translation = end($parts);
            if ($locale === 'en') {
                $translation = str_replace('_', ' ', $translation);
                $translation = ucwords($translation);
            }
        }

        App::setLocale($originalLocale);

        return $translation;
    }

    /**
     * Override in subclasses for extra per-export styling.
     */
    protected function applyCustomStyles(Worksheet $sheet) {}

    protected function getCounter(): int
    {
        return $this->counter++;
    }
}