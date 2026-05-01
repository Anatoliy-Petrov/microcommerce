<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class UpdateAddressRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'label'      => ['sometimes', 'string', 'max:50'],
            'line1'      => ['sometimes', 'string', 'max:255'],
            'line2'      => ['sometimes', 'nullable', 'string', 'max:255'],
            'city'       => ['sometimes', 'string', 'max:100'],
            'postcode'   => ['sometimes', 'string', 'max:20'],
            'country'    => ['sometimes', 'string', 'size:2'],
            'is_default' => ['sometimes', 'boolean'],
        ];
    }
}