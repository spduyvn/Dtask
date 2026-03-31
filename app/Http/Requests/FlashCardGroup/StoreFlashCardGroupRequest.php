<?php

namespace App\Http\Requests\FlashCardGroup;

use Illuminate\Foundation\Http\FormRequest;

class StoreFlashCardGroupRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
        ];
    }
}
