<?php

namespace App\Http\Requests\FlashCard;

use Illuminate\Foundation\Http\FormRequest;

class UpdateFlashCardRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'question' => 'sometimes|string',
            'answer' => 'sometimes|string',
            'flash_card_group_id' => 'nullable|integer|exists:flash_card_groups,id',
            'image' => 'nullable|image|max:10240', // 10MB limit
            'remove_image' => 'nullable|boolean',
        ];
    }
}
