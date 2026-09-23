<?php

namespace App\Http\Requests;

use App\Enums\TipoCuenta;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CuentaRequest extends FormRequest
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
        $cuenta = $this->route('cuenta');

        return [
            'nombre' => [
                'required',
                'string',
                'min:2',
                'max:80',
                // Dos cuentas con el mismo nombre serían indistinguibles al
                // elegir dónde registrar un movimiento.
                Rule::unique('cuentas')
                    ->where('user_id', $this->user()->id)
                    ->ignore($cuenta?->id),
            ],
            'tipo' => ['required', Rule::enum(TipoCuenta::class)],
            'saldo_inicial' => ['nullable', 'numeric', 'between:-999999999,999999999'],
            // Se guarda la moneda, pero de momento solo la principal: sumar
            // monedas distintas sin tasa de cambio no significaría nada.
            'moneda' => ['nullable', 'string', 'size:3', Rule::in([$this->user()->default_currency ?: 'COP'])],
            'color' => ['nullable', 'string', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'icono' => ['nullable', 'string', 'max:40'],
            'favorita' => ['nullable', 'boolean'],
            'orden' => ['nullable', 'integer', 'min:0', 'max:999'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'nombre.unique' => 'Ya tienes una cuenta con ese nombre.',
            'moneda.in' => 'Por ahora solo puedes crear cuentas en tu moneda principal.',
        ];
    }
}
