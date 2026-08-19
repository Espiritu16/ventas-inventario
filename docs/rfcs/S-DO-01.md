## RFC — S-DO-01 · Entorno reproducible
- RF que implementa: ninguno directamente. Habilita que cualquiera levante el sistema igual, incluido el proceso trabajador del que depende la emisión.
- Rol: `devops`
- Alcance de este sprint: Docker Compose con tres servicios —aplicación, PostgreSQL y proceso trabajador de la cola— levantables con un comando, más la documentación de cómo usarlo. **Explícitamente no**: despliegue a ningún servidor, pipeline de integración continua, respaldos ni observabilidad; todo eso es S-DO-02.
- Documentos relacionados: ADR-0001, ADR-0003 (el envío a SUNAT corre fuera de la petición), RNF-002
- Contratos que toca: ninguno
- Estado: aprobado
- Aprobado por: Kevin Espíritu (kevinespiritu16@gmail.com) — fecha: 2026-08-19

## Unidades de trabajo
| ID | Resultado observable | Deriva de | Depende de | Paralelizable con | Interfaz fijada | Criterio de cierre |
|---|---|---|---|---|---|---|
| UT-01 | Imagen de la aplicación con PHP 8.5 y sus extensiones, incluidas las que la firma digital necesita | ADR-0002, S-00 | ninguna | UT-02 | Dockerfile en la raíz | la imagen construye y `php artisan --version` responde dentro del contenedor |
| UT-02 | Servicio de PostgreSQL con volumen persistente y datos que sobreviven al reinicio | ADR-0001 | ninguna | UT-01 | `docker-compose.yml` | levantar, escribir, reiniciar y confirmar que el dato sigue ahí |
| UT-03 | Servicio del proceso trabajador de la cola, que reinicia solo si se cae | ADR-0003 | UT-01, UT-02 | ninguna | `php artisan queue:work` como comando del servicio | matar el proceso y confirmar que vuelve a levantar solo |
| UT-04 | Instrucciones de uso en el README y variables de entorno del entorno contenerizado, sin ningún secreto | AGENTS.md, RNF-014 | UT-01, UT-02, UT-03 | ninguna | sección "Cómo correrlo" del README | alguien que clona el repositorio levanta todo con un comando siguiendo solo el README |
