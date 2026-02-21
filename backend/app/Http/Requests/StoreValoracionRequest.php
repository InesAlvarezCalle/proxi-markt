<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class StoreValoracionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool {
        $compraventa = $this->route('compraventa');
        return Auth::id() == $compraventa->id_comprador || Auth::id() == $compraventa->id_vendedor;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array {
        return [
            'valoracion' => 'required|integer|between:1,5',
            'comentario' => 'nullable|string|max:500'
        ];
    }
}
