<?php

namespace App\Http\Requests\FlashCard;

use Illuminate\Foundation\Http\FormRequest;

class ReviewFlashCardRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'rating' => 'required|string|in:again,hard,good,easy,Again,Hard,Good,Easy',
        ];
    }
}
