<?php

namespace App\Compartido\Fechas;

/**
 * Tope de amplitud de cualquier consulta por rango de fechas civiles.
 *
 * Estaba escrito a mano en `VentaService` y en `ConsultaDeInventarioService`,
 * este último con el comentario «mismo tope que el kardex»: dos fuentes que
 * alguien tenía que acordarse de mantener iguales, y cambiar una sola dejaba la
 * suite entera en verde. Es el patrón recurrente de `docs/estado-global.md`, y
 * acá la duplicación sí se puede eliminar porque las dos fuentes son código.
 *
 * **Vive en `Compartido` y no en un dominio** porque no es de ninguno de los
 * dos: el kardex es de Inventario y los reportes son de Ventas, y ponerlo en
 * cualquiera de ellos obligaría al otro a depender de un dominio ajeno para
 * leer un límite que no le pertenece.
 *
 * El número nace en el contrato del kardex —`docs/contratos/inventario.md`,
 * `GET /inventario/kardex`, campo `hasta`— y `RangoDeFechasCoincideConElContratoTest`
 * comprueba que no se separen. Son 366 días: un año más un día de margen, para
 * que doce meses completos entren sin quedar justo en el borde.
 */
final class RangoDeFechas
{
    /**
     * Amplitud máxima en días de calendario, contando el primero y el último:
     * `desde` y `hasta` pueden distar `MAXIMO_DIAS - 1` días.
     */
    public const MAXIMO_DIAS = 366;
}
