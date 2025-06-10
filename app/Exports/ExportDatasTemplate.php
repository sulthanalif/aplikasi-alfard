<?php

namespace App\Exports;

use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Model;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\FromCollection;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Maatwebsite\Excel\Events\AfterSheet;

class ExportDatasTemplate implements FromCollection, WithHeadings, WithStyles, WithEvents, WithTitle
{
    private $model;
    private $title;
    private $headers;

    public function __construct(Model $model, string $title = 'Template', array $excludedColumns = [])
    {
        $this->model = $model;
        $this->title = $title;

        // Ambil fillable dari model
        $fillable = collect($model->getFillable());

        // Filter kolom yang dikecualikan
        if (!empty($excludedColumns)) {
            $fillable = $fillable->filter(fn ($column) => !in_array($column, $excludedColumns));
        }

        // Format header: ubah snake_case ke Title Case
        $this->headers = $fillable->map(fn ($header) => Str::title(str_replace('_', ' ', $header)))
                                  ->toArray();
    }

    public function collection()
    {
        return collect([]); // Template kosong
    }

    public function headings(): array
    {
        return $this->headers;
    }

    public function styles(Worksheet $sheet)
    {
        $highestColumn = $sheet->getHighestColumn();

        // Tambahkan judul di baris pertama
        $sheet->insertNewRowBefore(1, 1);
        $sheet->setCellValue('A1', $this->title);
        $sheet->mergeCells("A1:{$highestColumn}1");
        $sheet->getStyle('A1')->applyFromArray([
            'font' => ['bold' => true, 'size' => 14],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER
            ],
        ]);

        // Styling header
        $sheet->getStyle("A2:{$highestColumn}2")->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => '4F81BD']],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER
            ],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]
        ]);
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();

                // Auto-size setiap kolom
                foreach (range('A', $sheet->getHighestColumn()) as $column) {
                    $sheet->getColumnDimension($column)->setAutoSize(true);
                }
            },
        ];
    }

    public function title(): string
    {
        return $this->title;
    }
}
