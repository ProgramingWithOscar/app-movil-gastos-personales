<?php

namespace App\Http\Requests;

use App\Services\CalculadorPeriodos;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DashboardRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'periodo' => ['nullable', Rule::in(CalculadorPeriodos::TIPOS)],
            'desde' => ['required_if:periodo,personalizado', 'nullable', 'date_format:Y-m-d'],
            'hasta' => ['required_if:periodo,personalizado', 'nullable', 'date_format:Y-m-d', 'after_or_equal:desde'],
            'movimientos' => ['nullable', 'integer', 'min:1', 'max:50'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'desde.required_if' => 'Un período personalizado necesita una fecha inicial.',
            'hasta.required_if' => 'Un período personalizado necesita una fecha final.',
            'hasta.after_or_equal' => 'La fecha final no puede ser anterior a la inicial.',
        ];
    }

    public function periodo(): string
    {
        return $this->string('periodo')->toString() ?: 'mes';
    }

    public function cuantosMovimientos(): int
    {
        return $this->integer('movimientos') ?: 10;
    }
}
