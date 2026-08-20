<?php

namespace App\Compartido\Documentos;

/**
 * Tipos de documento de identidad del catálogo de SUNAT que el sistema usa.
 *
 * El valor es el código que viaja en el comprobante, no un identificador
 * propio: cambiarlo rompería los documentos ya emitidos.
 */
enum TipoDeDocumento: string
{
    case SIN_DOCUMENTO = '0';
    case DNI = '1';
    case CARNE_DE_EXTRANJERIA = '4';
    case RUC = '6';

    /**
     * Patrón exacto que debe cumplir el número, o null si el tipo no lleva.
     *
     * Los patrones son anclados y sin espacios a propósito: el contrato dice
     * que un documento con separadores se rechaza, no se limpia. Limpiarlo
     * significaría guardar un número distinto del que la persona escribió, y
     * ese número viaja al comprobante.
     */
    public function patron(): ?string
    {
        return match ($this) {
            self::SIN_DOCUMENTO => null,
            self::DNI => '/^[0-9]{8}$/',
            self::CARNE_DE_EXTRANJERIA => '/^[A-Za-z0-9]{1,12}$/',
            self::RUC => '/^[0-9]{11}$/',
        };
    }

    public function exigeNumero(): bool
    {
        return $this !== self::SIN_DOCUMENTO;
    }

    public function descripcion(): string
    {
        return match ($this) {
            self::SIN_DOCUMENTO => 'sin documento',
            self::DNI => 'DNI',
            self::CARNE_DE_EXTRANJERIA => 'carné de extranjería',
            self::RUC => 'RUC',
        };
    }

    /** @return array<int, string> */
    public static function valores(): array
    {
        return array_map(fn (self $tipo) => $tipo->value, self::cases());
    }
}
