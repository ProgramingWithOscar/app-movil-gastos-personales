<?php

/*
|--------------------------------------------------------------------------
| Mensajes propios de los movimientos
|--------------------------------------------------------------------------
|
| Viven aquí y no dentro del FormRequest porque tienen que cambiar con el
| idioma del perfil, igual que el catálogo de categorías. Un mensaje escrito a
| mano en PHP se queda en español aunque todo lo demás esté en inglés.
|
*/

return [

    'cuenta_requerida' => 'Elige la cuenta de la que sale o entra el dinero.',
    'cuenta_invalida' => 'Esa cuenta no existe, no es tuya o está archivada.',
    'categoria_invalida' => 'Esa categoría no existe o no es de este tipo de movimiento.',

];
