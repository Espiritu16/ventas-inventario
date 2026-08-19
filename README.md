# ventas-inventario

Sistema de ventas e inventario para una comercializadora: controla mercadería
por lotes con fecha de vencimiento, registra ventas descontando primero lo que
vence antes, y emite boletas y facturas electrónicas directamente ante SUNAT.

## Stack
- PHP 8.5 + Laravel (versión estable vigente al fundar el proyecto)
- Blade + Livewire + Tailwind CSS
- PostgreSQL — ver [ADR-0001](docs/decisiones/0001-motor-de-base-de-datos.md)
- Greenter para la emisión electrónica — ver [ADR-0002](docs/decisiones/0002-emision-electronica-propia.md)
- Gestor de paquetes: Composer (PHP) y pnpm (assets)

## Cómo correrlo

> El proyecto todavía no está fundado: estos comandos quedan operativos al
> completarse el sprint de fundación.

```bash
cp .env.example .env   # completar los valores requeridos; .env nunca se commitea
composer install
pnpm install
php artisan key:generate
php artisan migrate
php artisan serve
```

El envío de comprobantes a SUNAT corre en segundo plano, así que además del
servidor web hace falta el proceso trabajador:

```bash
php artisan queue:work
```

## Comandos
- Lint: `./vendor/bin/pint --test`
- Tests: `php artisan test`
- Build de assets: `pnpm build`
- Migraciones: `php artisan migrate`

## Estructura

Organización domain-first: cada dominio de negocio vive completo en
`app/Dominios/<Dominio>/`. Ver la skill `laravel-estructura`.

## Documentación técnica
- Requisitos: [docs/requisitos/](docs/requisitos/)
- Contratos internos: [docs/contratos/](docs/contratos/); experiencia e integración de la interfaz: [docs/frontend/](docs/frontend/)
- Modelo de datos: [docs/persistencia/modelo.md](docs/persistencia/modelo.md)
- Manejo de errores: [docs/errores/manejo-errores.md](docs/errores/manejo-errores.md)
- Integración con SUNAT: [docs/integraciones/sunat.md](docs/integraciones/sunat.md)
- Decisiones de arquitectura: [docs/decisiones/](docs/decisiones/)
- Roadmap y sprints: [docs/estado-global.md](docs/estado-global.md) y [docs/rfcs/](docs/rfcs/)

## Advertencia sobre SUNAT

Todo el desarrollo y toda la validación ocurren contra el **ambiente beta** de
SUNAT. Enviar comprobantes al ambiente de producción o usar el certificado
digital real exige autorización explícita del dueño del negocio, según
[AGENTS.md](AGENTS.md).

## Convenciones y gobernanza
Ver [AGENTS.md](AGENTS.md).
