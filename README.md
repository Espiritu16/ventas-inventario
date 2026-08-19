# ventas-inventario

Sistema de ventas e inventario para una comercializadora: controla mercadería
por lotes con fecha de vencimiento, registra ventas descontando primero lo que
vence antes, y emite boletas y facturas electrónicas directamente ante SUNAT.

## Stack
- PHP 8.5.9 + Laravel v13.26.1
- Blade + Livewire + Tailwind CSS 4.3.3, compilados con Vite 8.2.1
- PostgreSQL 18.3 — ver [ADR-0001](docs/decisiones/0001-motor-de-base-de-datos.md)
- Greenter para la emisión electrónica — ver [ADR-0002](docs/decisiones/0002-emision-electronica-propia.md)
- Gestor de paquetes: Composer 2.10.2 (PHP) y pnpm 11.22.0 sobre Node 24.19.0 (assets)

Las versiones quedaron verificadas al fundar el proyecto en S-00, contra la
fuente oficial y `composer.lock`.

## Cómo correrlo

Necesitas PostgreSQL corriendo, con un rol y una base para la aplicación y otra
base de pruebas terminada en `_test`. Los nombres por defecto van en `.env.example`.

```bash
cp .env.example .env   # completar los valores requeridos; .env nunca se commitea
composer install
pnpm install
php artisan key:generate
php artisan migrate
pnpm build             # o `pnpm dev` mientras desarrollas
php artisan serve
```

`pnpm build` no es opcional: sin los assets compilados, Vite no encuentra su
manifiesto y cualquier vista que extienda el layout base responde 500.

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
