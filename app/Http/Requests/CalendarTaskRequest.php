<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CalendarTaskRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'month' => 'required|integer|min:1|max:12',
            'year'  => 'required|integer|min:2000|max:2100',
        ];
    }

    public function messages(): array
    {
        return [
            'month.required' => 'Month is required.',
            'month.integer'  => 'Month must be an integer.',
            'month.min'      => 'Month must be between 1 and 12.',
            'month.max'      => 'Month must be between 1 and 12.',
            'year.required'  => 'Year is required.',
            'year.integer'   => 'Year must be an integer.',
            'year.min'       => 'Year must be between 2000 and 2100.',
            'year.max'       => 'Year must be between 2000 and 2100.',
        ];
    }
}
