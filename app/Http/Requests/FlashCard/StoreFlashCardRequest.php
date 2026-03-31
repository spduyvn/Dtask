<?php

namespace App\Http\Requests\FlashCard;

use Illuminate\Foundation\Http\FormRequest;

class StoreFlashCardRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'question' => 'required|string',
            'answer' => 'required|string',
            'flash_card_group_id' => 'required|integer|exists:flash_card_groups,id',
            'image' => 'nullable|image|max:10240', // 10MB limit
        ];
    }
}
