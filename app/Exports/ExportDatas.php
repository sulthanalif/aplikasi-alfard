<?php

namespace App\Exports;

use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Model;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Style\Border;
use Maatwebsite\Excel\Concerns\WithHeadings;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use Maatwebsite\Excel\Concerns\FromCollection;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ExportDatas implements FromCollection, WithHeadings, WithStyles, WithEvents, WithTitle
{
    private $datas;
    private $title;
    private $headers;

    public function __construct($datas, $title, $headers)
    {
        $this->datas = $datas;
        $this->title = $title;
        $this->headers = $headers;
    }

    public function collection()
    {
        return $this->datas;
    }

    public function headings(): array
    {
        return $this->headers;
    }

    public function styles(Worksheet $sheet)
    {
        $highestColumn = $sheet->getHighestColumn();

        // Set styling untuk judul di baris pertama
        $sheet->insertNewRowBefore(1, 1); // Sisipkan baris baru untuk judul
        $sheet->setCellValue('A1', $this->title); // Set judul di sel A1
        $sheet->mergeCells("A1:{$highestColumn}1"); // Gabungkan semua kolom untuk judul
        $sheet->getStyle('A1')->applyFromArray([
            'font' => ['bold' => true, 'size' => 14],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
        ]);

        // Set styling untuk header di baris kedua (nama kolom)
        $sheet->getStyle('A2:' . $highestColumn . '2')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => '4F81BD']],
            'alignment' => ['horizontal' => 'center', 'vertical' => 'center'],
            'borders' => ['allBorders' => ['borderStyle' => 'thin', 'color' => ['rgb' => '000000']]],
        ]);

        // Set border untuk seluruh data
        $highestRow = $sheet->getHighestRow();
        $sheet->getStyle("A2:{$highestColumn}{$highestRow}")->applyFromArray([
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                ],
            ],
        ]);
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();

                // Auto-size untuk semua kolom
                foreach (range('A', $sheet->getHighestColumn()) as $column) {
                    $sheet->getColumnDimension($column)->setAutoSize(true);
                }

                // Tambahkan fitur AutoFilter untuk semua kolom
                $highestRow = $sheet->getHighestRow();
                $highestColumn = $sheet->getHighestColumn();
                $sheet->setAutoFilter("A2:{$highestColumn}{$highestRow}");
            },
        ];
    }

    public function title(): string
    {
        return $this->title;
    }
}
