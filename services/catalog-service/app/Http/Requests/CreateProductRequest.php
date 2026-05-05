<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class CreateProductRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'name'          => ['required', 'string', 'max:255'],
            'description'   => ['nullable', 'string'],
            'price'         => ['required', 'numeric', 'min:0'],
            'category_id'   => ['required', 'integer', 'exists:categories,id'],
            'sku'           => ['required', 'string', 'max:100', 'unique:products,sku'],
            'is_active'     => ['sometimes', 'boolean'],
            'initial_stock' => ['sometimes', 'integer', 'min:0'],
        ];
    }
}