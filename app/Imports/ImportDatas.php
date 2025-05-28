<?php

namespace App\Imports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Illuminate\Database\Eloquent\Model;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Illuminate\Support\Facades\Validator;

class ImportDatas implements ToCollection
{
    protected Model $model;
    protected array $rules;
    protected array $except;

    public function __construct(Model $model, array $rules = [], array $except = [])
    {
        $this->model = $model;
        $this->rules = $rules;
        $this->except = $except;
    }

    public function collection(Collection $rows)
    {
        $headings = $rows->get(1); // baris kedua (key data)
        $dataRows = $rows->slice(2); // data mulai baris ke-3

        $fillable = array_diff($this->model->getFillable(), $this->except);

        foreach ($dataRows as $index => $row) {
            $rowData = $headings->combine($row)->toArray();

            // Ambil data yang sesuai fillable & tidak termasuk $except
            $filteredData = array_intersect_key($rowData, array_flip($fillable));

            // Validasi (jika ada)
            if ($this->rules) {
                Validator::make($filteredData, $this->rules)->validate();
            }

            $this->model->create($filteredData);
        }
    }
}
