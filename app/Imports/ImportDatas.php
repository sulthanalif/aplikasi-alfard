<?php

namespace App\Imports;

use Illuminate\Database\Eloquent\Model;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Concerns\WithStartRow;

class ImportDatas implements ToModel, WithHeadingRow, WithStartRow
{
    protected Model $model;
    protected array $except;

    public function __construct(Model $model, array $except = [])
    {
        $this->model = $model;
        $this->except = $except;
    }

    /**
     * Start row untuk header row.
     * 2 = baris kedua (karena baris pertama adalah judul).
     */
    public function startRow(): int
    {
        return 2;
    }

    /**
     * Proses setiap row dari Excel ke model.
     *
     * @param array $row
     * @return Model|null
     */
    public function model(array $row)
    {
        try {
            $fillable = collect($this->model->getFillable())
                ->reject(fn ($field) => in_array($field, $this->except))
                ->toArray();

            // Normalisasi key agar lowercase (optional)
            $normalizedRow = collect($row)
                ->mapWithKeys(fn ($value, $key) => [strtolower($key) => $value])
                ->toArray();

            // Filter agar hanya yang termasuk fillable
            $filteredData = array_intersect_key($normalizedRow, array_flip($fillable));

            if (!empty($filteredData)) {
                return $this->model->newInstance($filteredData);
            }
        } catch (\Throwable $th) {
            return Log::chennel('debug')->error("Import error on row: " . json_encode($row) . " - " . $th->getMessage());
        }
    }
}
