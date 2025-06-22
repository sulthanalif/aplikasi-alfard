<?php

namespace App\Imports;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Concerns\WithStartRow;
use ReflectionClass;
use ReflectionMethod;

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
     * Get related models from relationship methods
     */
    protected function getRelationships(): array
    {
        $relationships = [];
        $reflection = new ReflectionClass($this->model);

        foreach ($reflection->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
            if ($method->getReturnType() && is_a($method->getReturnType()->getName(), BelongsTo::class, true)) {
                $relationships[] = $method->getName();
            }
        }

        return $relationships;
    }

    /**
     * Handle relationship fields by looking up related model by name if ID is not provided
     */
    protected function handleRelationships(array $data): array
    {
        $relationships = $this->getRelationships();

        foreach ($relationships as $relation) {
            $relationKey = strtolower($relation) . '_id';
            $nameKey = strtolower($relation) . '_name';

            if (isset($data[$nameKey]) && !isset($data[$relationKey])) {
                $relatedModel = $this->model->$relation()->getRelated();
                $related = $relatedModel->where('name', $data[$nameKey])->first();

                if ($related) {
                    $data[$relationKey] = $related->id;
                }
                unset($data[$nameKey]);
            }
        }

        return $data;
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

            // Handle relationships
            $filteredData = $this->handleRelationships($filteredData);

            if (!empty($filteredData)) {
                return $this->model->newInstance($filteredData);
            }
        } catch (\Throwable $th) {
            return Log::channel('debug')->error("Import error on row: " . json_encode($row) . " - " . $th->getMessage());
        }
    }
}
