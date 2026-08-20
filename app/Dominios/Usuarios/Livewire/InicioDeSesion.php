<?php

namespace App\Dominios\Usuarios\Livewire;

use App\Compartido\Autorizacion\Permiso;
use App\Compartido\Errores\ErrorDeDominio;
use App\Dominios\Usuarios\Servicios\UsuarioService;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

/**
 * Pantalla de acceso (RF-001).
 *
 * El nombre de esta clase lo fijó Arquitectura antes de que existiera, en
 * docs/requisitos/actores-permisos.md: es el único componente que puede
 * invocarse sin sesión, y la matriz de permisos lo declara en lista cerrada.
 * Renombrarla apagaría ese permiso en silencio.
 *
 * La autenticación se le pide a `UsuarioService` en el mismo proceso, como
 * declara docs/frontend/integracion.md: no hay cliente HTTP entre la interfaz
 * y el dominio. `POST /login` sigue existiendo como transición de sesión por
 * HTTP y no cambia; esta pantalla no lo reemplaza.
 *
 * Tres estados visibles: en reposo, enviando y error de credenciales.
 *
 * El atributo nombra la fila `GET /login` de la matriz, que es una de las
 * anónimas. No está diciendo "soy público": está nombrando una fila que la
 * matriz declara pública, y por eso este componente no puede autoconcederse
 * nada. Que además pueda invocarse sin sesión lo permite la lista cerrada de
 * `MatrizDePermisos`, que es otra cosa y la aprueba Arquitectura.
 */
#[Permiso('GET /login')]
class InicioDeSesion extends Component
{
    public string $email = '';

    public string $password = '';

    /**
     * Un solo mensaje para las tres causas posibles —correo inexistente,
     * usuario inactivo y contraseña equivocada—, porque el servicio devuelve
     * el mismo error para las tres a propósito. Distinguirlas acá delataría
     * cuál falló y desharía esa decisión.
     */
    public ?string $error = null;

    public function iniciar(UsuarioService $usuarios): void
    {
        $this->error = null;

        try {
            $usuario = $usuarios->autenticar($this->email, $this->password);
        } catch (ErrorDeDominio $fallo) {
            // La contraseña no sobrevive a un intento fallido: dejarla
            // escrita la expone en pantalla sin que nadie la haya vuelto a
            // pedir.
            $this->password = '';
            $this->error = $fallo->getMessage();

            // El foco vuelve al correo, que es donde se corrige el dato
            // (criterio de cierre de UT-02).
            $this->dispatch('foco-al-correo');

            return;
        }

        Auth::login($usuario);

        // Se regenera el identificador de sesión al autenticar: si se
        // conservara el anterior, una sesión preexistente quedaría elevada a
        // la del usuario que acaba de entrar. Misma razón que en
        // SesionController.
        session()->regenerate();

        // `intended` recupera la URL que se había pedido sin sesión; si no
        // hubo ninguna, va al panel.
        $this->redirectIntended('/panel');
    }

    public function render()
    {
        return view('livewire.usuarios.inicio-de-sesion');
    }
}
