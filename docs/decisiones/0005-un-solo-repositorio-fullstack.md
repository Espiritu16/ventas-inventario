# ADR-0005: Un solo repositorio fullstack con Laravel y Livewire

- Estado: aceptada
- Aprobado por (Arquitectura): sesión de Arquitectura del 2026-08-19, sobre la decisión de organización tomada por Kevin Espíritu — fecha: 2026-08-19

## Contexto

El usuario pidió trabajar los sprints separados en backend y frontend, para
poder avanzar ambos frentes en paralelo. Eso admitía dos lecturas: separar los
proyectos (una API y una aplicación de interfaz aparte) o separar solo la
planificación dentro de un mismo proyecto. Se decidió lo segundo.

## Decisión

Un único repositorio, `ventas-inventario`, con Laravel sirviendo la interfaz
mediante Blade y Livewire. La separación backend/frontend es de planificación:
los sprints de backend construyen dominio, persistencia, reglas y procesos; los
de frontend construyen las pantallas que los consumen.

La organización interna es domain-first: cada dominio de negocio vive en
`app/Dominios/<Dominio>/` con su modelo, controlador, servicio y validaciones
juntos, según la convención de `laravel-estructura`.

## Consecuencias

- No hay contrato HTTP público que congelar antes de empezar el frontend, ni dos despliegues ni dos configuraciones que mantener.
- `docs/contratos/` documenta las operaciones internas de cada dominio —las rutas web y las acciones de los servicios— y no una API para terceros. Si algún día se expone una API pública, será un contrato nuevo con su propia versión.
- Un sprint de frontend depende de que las operaciones que consume existan; esa dependencia se declara explícitamente en el roadmap para que el paralelismo sea real y no una ilusión.
