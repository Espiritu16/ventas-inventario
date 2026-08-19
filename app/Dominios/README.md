# app/Dominios

Cada dominio de negocio vive completo en su propia carpeta —modelo, servicio,
validación, tipos y componentes Livewire juntos—, nunca repartido por capa
técnica. La estructura de un dominio es:

```
app/Dominios/<Dominio>/
├── Modelos/        # modelos Eloquent del dominio
├── Servicios/      # servicios de docs/contratos/servicios-de-dominio.md
├── Datos/          # objetos de entrada y salida de esos servicios
└── Livewire/       # componentes de interfaz — los escribe implementation-frontend
```

Namespace: `App\Dominios\<Dominio>`. Ninguna carpeta es obligatoria de
antemano: cada sprint crea las que su dominio necesita.

Los dominios previstos, con el sprint que crea cada uno, están en
`docs/estado-global.md`. La única regla que se verifica de forma automática es
que ninguna clase de dominio viva fuera de aquí: la comprueba
`tests/Unit/Arquitectura/EstructuraDeDominiosTest.php`.

Lo que **no** es de un dominio en particular —helpers, tipos y reglas
compartidas por varios— vive en `app/Compartido/`.
