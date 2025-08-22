<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use Maatwebsite\Excel\Concerns\Exportable;
use Illuminate\Support\Collection;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

class DynamicExport implements FromCollection, WithTitle, WithEvents
{
    use Exportable;

    private $title;
    private $depo;
    private $periode;
    private $stats;
    private $data;
    private $headers;

    /**
     * Konstruktor diperbarui untuk menyusun header secara terpisah.
     *
     * @param array      $stats      Data statistik untuk ditampilkan di atas tabel.
     * @param Collection $data       Koleksi data untuk tabel utama.
     * @param array      $headers    Header untuk tabel data.
     * @param string     $startDate  Tanggal mulai laporan.
     * @param string     $endDate    Tanggal akhir laporan.
     */
    public function __construct(string $title, string $periode, array $stats, Collection $data, array $headers)
    {
        $this->stats = $stats;
        $this->data = $data;
        $this->headers = $headers;
        $this->title = $title;
        $this->periode = $periode;

        // Menetapkan properti untuk header secara terpisah
        $this->depo = 'Depo AMDK Al Ma\'soem Banyuresmi Garut';
    }

    /**
     * Mengembalikan koleksi kosong karena semua data akan ditangani
     * secara manual di dalam event AfterSheet untuk kontrol tata letak penuh.
     */
    public function collection()
    {
        return new Collection();
    }

    /**
     * Judul untuk tab sheet di file Excel.
     */
    public function title(): string
    {
        return $this->title;
    }

    /**
     * Mendaftarkan event AfterSheet untuk memanipulasi sheet
     * setelah data dasar dibuat. Di sinilah semua "keajaiban" terjadi.
     */
    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $startColChar = 'A';
                $lastColIndex = count($this->headers) - 1;
                $lastColChar = chr(ord($startColChar) + $lastColIndex);

                // Variabel untuk melacak baris saat ini, membuat tata letak lebih mudah dikelola
                $currentRow = 1;

                // --- 1. JUDUL, DEPO, DAN PERIODE ---
                // Baris 1: Judul
                $sheet->setCellValue('A' . $currentRow, $this->title);
                $sheet->mergeCells($startColChar . $currentRow . ':' . $lastColChar . $currentRow);
                $sheet->getStyle($startColChar . $currentRow)->applyFromArray([
                    'font' => ['bold' => true, 'size' => 16],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                ]);

                // Baris 2: Depo
                $currentRow++;
                $sheet->setCellValue('A' . $currentRow, $this->depo);
                $sheet->mergeCells($startColChar . $currentRow . ':' . $lastColChar . $currentRow);
                $sheet->getStyle($startColChar . $currentRow)->applyFromArray([
                    'font' => ['bold' => true, 'size' => 14],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                ]);

                // Baris 3: Periode
                $currentRow++;
                $sheet->setCellValue('A' . $currentRow, $this->periode);
                $sheet->mergeCells($startColChar . $currentRow . ':' . $lastColChar . $currentRow);
                $sheet->getStyle($startColChar . $currentRow)->applyFromArray([
                    'font' => ['bold' => true, 'size' => 12],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                ]);


                // --- 2. STATISTIK (STATS) ---
                $currentRow += 2; // Memberi jarak dari header
                $statsStartRow = $currentRow;
                foreach ($this->stats as $index => $stat) {
                    $sheet->setCellValue($startColChar . ($statsStartRow + $index), key($stat) . ': ' . current($stat));
                    $sheet->getStyle($startColChar . ($statsStartRow + $index))->getFont()->setBold(true);
                }
                $currentRow += count($this->stats);


                // --- 3. TABEL DATA (DATA TABLE) ---
                $currentRow += 2; // Memberi jarak sebelum tabel
                $headerStartRow = $currentRow;
                $headerRange = $startColChar . $headerStartRow . ':' . $lastColChar . $headerStartRow;

                // Menulis header tabel
                $sheet->fromArray($this->headers, null, $startColChar . $headerStartRow);

                // Memberi style pada header
                $sheet->getStyle($headerRange)->applyFromArray([
                    'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '4F81BD']],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
                    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '000000']]],
                ]);

                // Menulis data tabel
                $dataStartRow = $headerStartRow + 1;
                $sheet->fromArray($this->data->toArray(), null, $startColChar . $dataStartRow);

                // Memberi style pada data
                $lastDataRow = $dataStartRow + $this->data->count() - 1;
                if ($this->data->isNotEmpty()) {
                    $dataRange = $startColChar . $dataStartRow . ':' . $lastColChar . $lastDataRow;
                    $sheet->getStyle($dataRange)->applyFromArray([
                        'borders' => [
                            'allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '000000']],
                        ],
                        'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                    ]);
                } else {
                    $lastDataRow = $dataStartRow; // Menghindari error jika data kosong
                }


                // --- 4. TANDA TANGAN (SIGNATURE) ---
                $currentRow = $lastDataRow + 3; // Memberi jarak 3 baris dari tabel

                // Mendefinisikan kolom untuk tanda tangan kiri dan kanan
                $leftSigCol = 'A';
                $rightSigCol = chr(ord($lastColChar) - 2);

                // Baris pertama: Label "Mengetahui," dan Tanggal
                $sheet->setCellValue($leftSigCol . $currentRow, 'Mengetahui,');
                $sheet->getStyle($leftSigCol . $currentRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

                $sheet->setCellValue($rightSigCol . $currentRow, 'Garut, ' . \Carbon\Carbon::now()->locale('id')->translatedFormat('d F Y'));
                $sheet->mergeCells($rightSigCol . $currentRow . ':' . $lastColChar . $currentRow);
                $sheet->getStyle($rightSigCol . $currentRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

                // Memberi ruang vertikal untuk tanda tangan
                $currentRow += 4;

                // Baris kedua: Nama penanda tangan
                // Tanda Tangan Kiri
                $sheet->setCellValue($leftSigCol . $currentRow, 'Rendi Suryawan');
                $sheet->getStyle($leftSigCol . $currentRow)->applyFromArray([
                    'font' => ['bold' => true],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                ]);

                // Tanda Tangan Kanan
                $sheet->setCellValue($rightSigCol . $currentRow, 'Agus Mansur');
                $sheet->mergeCells($rightSigCol . $currentRow . ':' . $lastColChar . $currentRow);
                $sheet->getStyle($rightSigCol . $currentRow)->applyFromArray([
                    'font' => ['bold' => true],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                ]);


                // --- 5. PENYESUAIAN AKHIR (FINAL ADJUSTMENTS) ---
                // Mengatur lebar kolom otomatis
                foreach (range($startColChar, $lastColChar) as $columnID) {
                    $sheet->getColumnDimension($columnID)->setAutoSize(true);
                }
                // Menambahkan AutoFilter pada header tabel
                $sheet->setAutoFilter($headerRange);
            },
        ];
    }
}
