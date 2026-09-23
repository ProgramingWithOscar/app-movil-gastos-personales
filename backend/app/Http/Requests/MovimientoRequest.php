<?php

namespace App\Http\Requests;

use App\Enums\CategoriaMovimiento;
use App\Enums\TipoMovimiento;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class MovimientoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Una categoría vacía es `otros`, no `null`.
     *
     * La columna no admite nulos, así que mandar `categoria: null` reventaba
     * contra la base en vez de dar un 422. Y no clasificar un movimiento es
     * justo lo que significa `otros`.
     */
    protected function prepareForValidation(): void
    {
        if (blank($this->input('categoria'))) {
            $this->merge(['categoria' => CategoriaMovimiento::Otros->value]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'cuenta_id' => [
                'required',
                'integer',
                // La cuenta tiene que ser suya y estar activa: en una archivada
                // ya no se registra nada, aunque conserve su historial.
                Rule::exists('cuentas', 'id')
                    ->where('user_id', $this->user()->id)
                    // 0 y no `false`: PDO enlaza los booleanos de forma
                    // distinta según el driver, y en SQLite la comparación no
                    // casaba. Con el entero funciona igual en todos.
                    ->where('archivada', 0),
            ],
            'tipo' => ['required', Rule::enum(TipoMovimiento::class)],
            'descripcion' => ['required', 'string', 'max:255'],
            // El importe va siempre positivo: el signo lo pone el tipo.
            'monto' => ['required', 'numeric', 'min:0.01'],
            'categoria' => ['required', Rule::in($this->categoriasDelTipo())],
            'fecha' => ['required', 'date'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'cuenta_id.required' => __('movimientos.cuenta_requerida'),
            'cuenta_id.exists' => __('movimientos.cuenta_invalida'),
            'categoria.in' => __('movimientos.categoria_invalida'),
        ];
    }

    /**
     * Las categorías válidas para el tipo que trae la petición.
     *
     * Se filtra por tipo a propósito: `salario` en un gasto y `vivienda` en un
     * ingreso son errores, aunque las dos existan en el catálogo.
     *
     * Con un tipo inválido devuelve todas, porque el propio `tipo` ya va a
     * fallar su regla. Dar además un error de categoría señalaría dos
     * problemas donde solo hay uno.
     *
     * @return array<int, string>
     */
    private function categoriasDelTipo(): array
    {
        $tipo = TipoMovimiento::tryFrom((string) $this->input('tipo'));

        return array_map(
            fn (CategoriaMovimiento $categoria) => $categoria->value,
            CategoriaMovimiento::de($tipo),
        );
    }
}
