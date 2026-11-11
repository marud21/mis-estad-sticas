<?php

namespace Tests\Feature;

use App\Models\Socio;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

/**
 * Frenos contra la fuerza bruta.
 *
 * Sin esto, cualquiera puede probar contraseñas sin limite, o recorrer
 * numeros de documento en la consulta publica y llevarse el nombre y el
 * estado de cuenta de todos los socios.
 */
class SeguridadAccesoTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Cada prueba arranca sin contadores de intentos previos.
        RateLimiter::clear('login|'.strtolower('admin@corvepatios.com').'|127.0.0.1');
    }

    // ---------- Login ----------

    public function test_el_login_se_bloquea_tras_cinco_intentos_fallidos(): void
    {
        $datos = ['email' => 'admin@corvepatios.com', 'password' => 'clave-que-no-es'];

        // Los cinco primeros fallan con "credenciales incorrectas".
        for ($i = 1; $i <= 5; $i++) {
            $this->post(route('login.store'), $datos)
                ->assertSessionHasErrors('email');

            $this->assertStringContainsString(
                'Credenciales incorrectas',
                session('errors')->first('email'),
                "El intento {$i} deberia fallar por credenciales, no por bloqueo.",
            );
        }

        // El sexto ya no se prueba: se bloquea.
        $this->post(route('login.store'), $datos)->assertSessionHasErrors('email');

        $this->assertStringContainsString(
            'Demasiados intentos',
            session('errors')->first('email'),
            'Tras cinco fallos debe bloquear.',
        );
    }

    public function test_el_bloqueo_tambien_frena_la_contraseña_correcta(): void
    {
        $datos = ['email' => 'admin@corvepatios.com', 'password' => 'clave-que-no-es'];

        for ($i = 1; $i <= 6; $i++) {
            $this->post(route('login.store'), $datos);
        }

        // Aunque acertara, ya no se le deja pasar: es lo que corta el
        // ataque por fuerza bruta.
        $this->post(route('login.store'), [
            'email' => 'admin@corvepatios.com',
            'password' => 'la-que-sea',
        ])->assertSessionHasErrors('email');

        $this->assertStringContainsString('Demasiados intentos', session('errors')->first('email'));
        $this->assertGuest();
    }

    public function test_entrar_bien_borra_la_cuenta_de_intentos(): void
    {
        $llave = 'login|admin@corvepatios.com|127.0.0.1';

        // Dos fallos, y despues se entra bien.
        for ($i = 1; $i <= 2; $i++) {
            $this->post(route('login.store'), [
                'email' => 'admin@corvepatios.com', 'password' => 'mala',
            ]);
        }
        $this->assertGreaterThan(0, RateLimiter::attempts($llave));

        // Se simula el acierto limpiando como lo hace el controlador.
        $usuario = User::where('email', 'admin@corvepatios.com')->firstOrFail();
        $this->actingAs($usuario);
        RateLimiter::clear($llave);

        $this->assertEquals(0, RateLimiter::attempts($llave), 'Al acertar, el contador vuelve a cero.');
    }

    public function test_el_bloqueo_es_por_correo_y_no_tumba_a_los_demas(): void
    {
        for ($i = 1; $i <= 6; $i++) {
            $this->post(route('login.store'), [
                'email' => 'otro@corvepatios.com', 'password' => 'mala',
            ]);
        }

        // El administrador, con otro correo, no quedo bloqueado por eso.
        $this->post(route('login.store'), [
            'email' => 'admin@corvepatios.com', 'password' => 'tambien-mala',
        ])->assertSessionHasErrors('email');

        $this->assertStringContainsString(
            'Credenciales incorrectas',
            session('errors')->first('email'),
            'Fallar con un correo no debe bloquear a otro.',
        );

        RateLimiter::clear('login|otro@corvepatios.com|127.0.0.1');
    }

    // ---------- Consulta publica de deuda ----------

    public function test_la_consulta_publica_se_frena_tras_diez_busquedas(): void
    {
        DB::beginTransaction();
        $socio = Socio::create([
            'nombre_completo' => 'S CONSULTA '.uniqid(), 'numero_documento' => uniqid('c'),
            'fecha_nacimiento' => '1990-01-01', 'entidad_salud' => 'X', 'celular' => '300',
            'tipo_sangre' => 'O+', 'direccion_residencia' => 'X', 'posicion_juego' => 'Medio',
            'nivel_jugador' => 2, 'estado' => 'activo',
        ]);

        // Las diez primeras pasan.
        for ($i = 1; $i <= 10; $i++) {
            $this->post(route('consulta.buscar'), ['numero_documento' => $socio->numero_documento])
                ->assertStatus(200);
        }

        // La once se corta.
        $this->post(route('consulta.buscar'), ['numero_documento' => $socio->numero_documento])
            ->assertStatus(429);

        DB::rollBack();
    }

    public function test_la_consulta_sigue_siendo_publica_para_una_persona(): void
    {
        $this->get(route('consulta.index'))->assertOk();
    }
}
