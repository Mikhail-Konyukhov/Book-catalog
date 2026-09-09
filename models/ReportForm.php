<?php

declare(strict_types=1);

namespace app\models;

use yii\base\Model;

class ReportForm extends Model
{
    public $year;

    public function rules(): array
    {
        return [
            ['year', 'required'],
            ['year', 'integer', 'min' => 1450, 'max' => (int) date('Y') + 1],
        ];
    }

    public function attributeLabels(): array
    {
        return ['year' => 'Год'];
    }
}
