<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class StoreAddressRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'label'      => ['required', 'string', 'max:50'],
            'line1'      => ['required', 'string', 'max:255'],
            'line2'      => ['nullable', 'string', 'max:255'],
            'city'       => ['required', 'string', 'max:100'],
            'postcode'   => ['required', 'string', 'max:20'],
            'country'    => ['required', 'string', 'size:2'],
            'is_default' => ['sometimes', 'boolean'],
        ];
    }
}