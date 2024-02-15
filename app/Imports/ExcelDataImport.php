<?php

namespace App\Imports;



use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Jenssegers\Mongodb\Eloquent\Model;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use App\Models\Korwil;
use Exception;

class ExcelDataImport implements ToModel, WithHeadingRow
{
    protected $modelClass;
    protected $mapping;

    public function __construct(string $modelClass, array $mapping)
    {
        $this->modelClass = $modelClass;
        $this->mapping = $mapping;
    }

    public function model(array $row)
    {
        $modelClass = $this->modelClass;

        $data = [];
        foreach ($this->mapping as $attribute => $column) {
            $data[$attribute] = $row[$column] ?? null;
        }

        // Instantiate the model and fill it with data
        $model = new $modelClass($data);

        // Save the model to MongoDB
        $model->save();

        return $model;
    }
}
