<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Color;
use Maatwebsite\Excel\Events\AfterSheet;
use Maatwebsite\Excel\Concerns\WithEvents;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use Maatwebsite\Excel\Concerns\Exportable;
use Illuminate\Support\Collection;

class DynamicExport implements FromCollection, WithHeadings, WithTitle, ShouldAutoSize, WithStyles, WithEvents
{
    use Exportable;

    private $title;
    private $stats;
    private $data;
    private $headers;
    private $startDataRow; // Properti baru untuk melacak baris awal data

    public function __construct(string $title, array $stats, Collection $data, array $headers)
    {
        $this->title = $title;
        $this->stats = $stats;
        $this->data = $data;
        $this->headers = $headers;
    }

    // Karena kita akan menempatkan header dan data secara manual di AfterSheet,
    // method collection() akan mengembalikan koleksi kosong untuk menghindari duplikasi.
    public function collection()
    {
        return new Collection(); // Mengembalikan koleksi kosong karena data akan ditangani di AfterSheet
    }

    // Method headings() tidak lagi diperlukan karena header akan ditempatkan manual
    public function headings(): array
    {
        return []; // Mengembalikan array kosong
    }

    public function title(): string
    {
        return $this->title;
    }

    public function styles(Worksheet $sheet)
    {
        // Styling dasar, penempatan konten akan di AfterSheet
        return [];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();

                // --- 1. Penempatan dan Styling Judul ---
                $titleRow = 1; // Baris untuk judul
                $startColASCII = 65; // ASCII untuk 'A'
                $endColASCII = $startColASCII + count($this->headers); // Kolom terakhir untuk digabungkan

                $sheet->setCellValue(chr($startColASCII) . $titleRow, $this->title);
                $sheet->mergeCells(chr($startColASCII) . $titleRow . ':' . chr($endColASCII) . $titleRow);
                $sheet->getStyle(chr($startColASCII) . $titleRow)->applyFromArray([
                    'font' => ['bold' => true, 'size' => 18], // Ukuran font lebih besar untuk judul
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
                ]);

                // --- 2. Penempatan dan Styling Statistik ---
                $statsStartRow = $titleRow + 2; // Statistik dimulai 2 baris setelah judul
                $this->startDataRow = $statsStartRow + count($this->stats) + 2; // Tentukan baris awal untuk header data

                $statCol = chr($startColASCII); // Kolom 'A' untuk statistik
                foreach ($this->stats as $index => $stat) {
                    $sheet->setCellValue($statCol . ($statsStartRow + $index), key($stat) . ': ' . current($stat));
                    $sheet->getStyle($statCol . ($statsStartRow + $index))->applyFromArray([
                        'font' => ['bold' => true],
                    ]);
                }

                // --- 3. Penempatan dan Styling Header Tabel Data ---
                $headerColStart = chr($startColASCII); // Kolom 'A'
                $headerColEnd = chr($startColASCII + count($this->headers) - 1); // Kolom terakhir dari header
                $headerRange = $headerColStart . $this->startDataRow . ':' . $headerColEnd . $this->startDataRow;

                // Set nilai header
                $colIndex = 0;
                foreach ($this->headers as $header) {
                    $sheet->setCellValue(chr($startColASCII + $colIndex) . $this->startDataRow, $header);
                    $colIndex++;
                }

                // Style untuk header
                $sheet->getStyle($headerRange)->applyFromArray([
                    'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '4F81BD']], // Warna biru
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
                    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '000000']]],
                ]);

                // --- 4. Penempatan dan Styling Data Tabel ---
                $dataStartRow = $this->startDataRow + 1;
                $lastColumn = $headerColEnd;
                $lastRow = $dataStartRow + $this->data->count() - 1;

                $rowIndex = $dataStartRow;
                foreach ($this->data as $row) {
                    $colIndex = 0;
                    foreach ($row as $cellValue) {
                        $sheet->setCellValue(chr($startColASCII + $colIndex) . $rowIndex, $cellValue);
                        $colIndex++;
                    }
                    $rowIndex++;
                }

                // Styling untuk baris data
                $sheet->getStyle(chr($startColASCII) . $dataStartRow . ':' . $lastColumn . $lastRow)->applyFromArray([
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_THIN,
                        ],
                    ],
                ]);

                // Alignment untuk semua data
                $sheet->getStyle(chr($startColASCII) . $dataStartRow . ':' . $lastColumn . $lastRow)
                      ->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

                // --- 5. Auto-size & AutoFilter ---
                // Auto-size untuk semua kolom yang digunakan
                foreach (range(chr($startColASCII), $lastColumn) as $column) {
                    $sheet->getColumnDimension($column)->setAutoSize(true);
                }

                // Tambahkan fitur AutoFilter untuk header data
                $sheet->setAutoFilter($headerRange);
            },
        ];
    }
}
