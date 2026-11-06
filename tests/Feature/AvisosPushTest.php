<?php

namespace Tests\Feature;

use App\Jobs\EnviarAvisoPush;
use App\Models\Cargo;
use App\Models\Equipo;
use App\Models\IncidenciaPartido;
use App\Models\Pago;
use App\Models\Partido;
use App\Models\Socio;
use App\Models\SuscripcionPush;
use App\Models\Torneo;
use App\Services\NotificacionPushService;
use App\Services\RegistroPartidoService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class AvisosPushTest extends TestCase
{
    /**
     * Punto de curva P-256 de verdad, de 65 bytes, igual al que entrega un
     * navegador. Va fijo y no generado al vuelo porque openssl necesita su
     * archivo de configuracion para crear claves EC, y en Windows no
     * siempre lo encuentra: la prueba no deberia depender de eso.
     */
    private const CLAVE_P256DH = 'BDg4iN9Hn48DCB3YcaFYC6P4qu0A58yRQfQDz2FWLciLePLR_3FyKqgkRWxiV6zJF6Yq7SMN-tkwjSqFQ-b69HY';

    /** Datos como los que entrega un navegador de verdad. */
    private function datosDeSuscripcion(string $sufijo = 'a'): array
    {
        return [
            'endpoint' => 'https://fcm.googleapis.com/fcm/send/prueba-'.$sufijo.'-'.uniqid(),
            'clave_p256dh' => self::CLAVE_P256DH,
            'clave_auth' => $this->base64Url(random_bytes(16)),
        ];
    }

    public function test_la_clave_de_prueba_es_un_punto_de_curva_valido(): void
    {
        $crudo = base64_decode(strtr(self::CLAVE_P256DH, '-_', '+/'), true);

        $this->assertEquals(65, strlen($crudo), 'Un punto P-256 sin comprimir pesa 65 bytes.');
        $this->assertEquals("\x04", $crudo[0], 'Debe empezar con el marcador de punto sin comprimir.');
    }

    private function base64Url(string $crudo): string
    {
        return rtrim(strtr(base64_encode($crudo), '+/', '-_'), '=');
    }

    /** @return array{0:Torneo,1:Equipo,2:Equipo,3:Socio} */
    private function escenario(): array
    {
        $t = Torneo::create(['nombre' => 'Torneo push '.uniqid(), 'fecha_inicio' => '2030-01-01']);
        $a = Equipo::create(['nombre' => 'PA '.uniqid(), 'torneo_id' => $t->id, 'categoria' => '50', 'estado' => 'activo']);
        $b = Equipo::create(['nombre' => 'PB '.uniqid(), 'torneo_id' => $t->id, 'categoria' => '50', 'estado' => 'activo']);

        $socio = Socio::create([
            'nombre_completo' => 'P PUSH '.uniqid(), 'numero_documento' => uniqid('p'),
            'fecha_nacimiento' => '1990-01-01', 'entidad_salud' => 'X', 'celular' => '300',
            'tipo_sangre' => 'O+', 'direccion_residencia' => 'X', 'posicion_juego' => 'Medio',
            'nivel_jugador' => 2, 'estado' => 'activo',
        ]);
        $socio->equipos()->attach($a->id);

        return [$t, $a, $b, $socio];
    }

    // ---------- Suscribirse ----------

    public function test_guarda_la_suscripcion_de_un_navegador(): void
    {
        DB::beginTransaction();
        $datos = $this->datosDeSuscripcion();

        $this->postJson(route('avisos.suscribir'), $datos)->assertOk();

        $guardada = SuscripcionPush::where('endpoint_huella', SuscripcionPush::huella($datos['endpoint']))->first();
        $this->assertNotNull($guardada);
        $this->assertEquals($datos['clave_p256dh'], $guardada->clave_p256dh);
        DB::rollBack();
    }

    public function test_suscribirse_dos_veces_no_duplica(): void
    {
        DB::beginTransaction();
        $datos = $this->datosDeSuscripcion();

        $this->postJson(route('avisos.suscribir'), $datos)->assertOk();
        $this->postJson(route('avisos.suscribir'), $datos)->assertOk();

        $this->assertEquals(
            1,
            SuscripcionPush::where('endpoint_huella', SuscripcionPush::huella($datos['endpoint']))->count(),
            'El mismo navegador no puede quedar dos veces en la lista.'
        );
        DB::rollBack();
    }

    public function test_rechaza_una_suscripcion_incompleta(): void
    {
        DB::beginTransaction();
        $antes = SuscripcionPush::count();

        $this->postJson(route('avisos.suscribir'), ['endpoint' => 'https://ejemplo.test/x'])
            ->assertStatus(422);

        $this->assertEquals($antes, SuscripcionPush::count());
        DB::rollBack();
    }

    public function test_rechaza_claves_con_formato_invalido(): void
    {
        DB::beginTransaction();
        $antes = SuscripcionPush::count();
        $base = $this->datosDeSuscripcion();

        // Una clave que no es un punto de curva valido reventaria el cifrado
        // al enviar, y como el envio es en lote arruinaria el aviso de todos.
        $this->postJson(route('avisos.suscribir'), array_merge($base, ['clave_p256dh' => 'texto-cualquiera']))
            ->assertStatus(422)
            ->assertJsonValidationErrors('clave_p256dh');

        $this->postJson(route('avisos.suscribir'), array_merge($base, ['clave_auth' => 'corta']))
            ->assertStatus(422)
            ->assertJsonValidationErrors('clave_auth');

        $this->assertEquals($antes, SuscripcionPush::count(), 'Ninguna clave mala entra a la lista.');
        DB::rollBack();
    }

    public function test_cualquiera_puede_suscribirse_sin_iniciar_sesion(): void
    {
        DB::beginTransaction();
        $this->postJson(route('avisos.suscribir'), $this->datosDeSuscripcion())
            ->assertOk()
            ->assertJsonStructure(['mensaje']);
        DB::rollBack();
    }

    public function test_desuscribirse_borra_la_suscripcion(): void
    {
        DB::beginTransaction();
        $datos = $this->datosDeSuscripcion();
        $this->postJson(route('avisos.suscribir'), $datos)->assertOk();

        $this->postJson(route('avisos.desuscribir'), ['endpoint' => $datos['endpoint']])->assertOk();

        $this->assertEquals(
            0,
            SuscripcionPush::where('endpoint_huella', SuscripcionPush::huella($datos['endpoint']))->count()
        );
        DB::rollBack();
    }

    // ---------- El aviso al registrar un resultado ----------

    public function test_registrar_un_resultado_encola_el_aviso(): void
    {
        Queue::fake();
        DB::beginTransaction();
        [$t, $a, $b, $socio] = $this->escenario();
        $partido = Partido::create([
            'torneo_id' => $t->id, 'equipo_local_id' => $a->id, 'equipo_visitante_id' => $b->id,
            'fecha_hora' => '2030-02-01 10:00', 'estado' => Partido::ESTADO_PROGRAMADO,
        ]);

        app(RegistroPartidoService::class)->registrar($partido, [
            ['socio_id' => $socio->id, 'equipo_id' => $a->id, 'tipo' => IncidenciaPartido::TIPO_GOL],
            ['socio_id' => $socio->id, 'equipo_id' => $a->id, 'tipo' => IncidenciaPartido::TIPO_GOL],
        ]);

        Queue::assertPushed(EnviarAvisoPush::class, 1);
        DB::rollBack();
    }

    public function test_el_aviso_lleva_el_marcador_y_los_equipos(): void
    {
        Queue::fake();
        DB::beginTransaction();
        [$t, $a, $b, $socio] = $this->escenario();
        $partido = Partido::create([
            'torneo_id' => $t->id, 'equipo_local_id' => $a->id, 'equipo_visitante_id' => $b->id,
            'fecha_hora' => '2030-02-01 10:00', 'estado' => Partido::ESTADO_PROGRAMADO,
        ]);

        app(RegistroPartidoService::class)->registrar($partido, [
            ['socio_id' => $socio->id, 'equipo_id' => $a->id, 'tipo' => IncidenciaPartido::TIPO_GOL],
        ]);

        Queue::assertPushed(function (EnviarAvisoPush $job) use ($a, $b) {
            $cuerpo = (new \ReflectionClass($job))->getProperty('cuerpo');
            $texto = $cuerpo->getValue($job);

            return str_contains($texto, $a->nombre)
                && str_contains($texto, $b->nombre)
                && str_contains($texto, '1 - 0');
        });
        DB::rollBack();
    }

    public function test_el_resultado_se_guarda_aunque_no_haya_quien_reciba(): void
    {
        DB::beginTransaction();
        [$t, $a, $b, $socio] = $this->escenario();
        SuscripcionPush::query()->delete();
        $partido = Partido::create([
            'torneo_id' => $t->id, 'equipo_local_id' => $a->id, 'equipo_visitante_id' => $b->id,
            'fecha_hora' => '2030-02-01 10:00', 'estado' => Partido::ESTADO_PROGRAMADO,
        ]);

        app(RegistroPartidoService::class)->registrar($partido, [
            ['socio_id' => $socio->id, 'equipo_id' => $a->id, 'tipo' => IncidenciaPartido::TIPO_GOL],
        ]);

        $partido->refresh();
        $this->assertEquals(Partido::ESTADO_JUGADO, $partido->estado);
        $this->assertEquals(1, $partido->goles_local);
        DB::rollBack();
    }

    public function test_sin_claves_no_se_intenta_enviar_nada(): void
    {
        DB::beginTransaction();
        config(['push.vapid.public_key' => null, 'push.vapid.private_key' => null]);

        $servicio = app(NotificacionPushService::class);
        $this->assertFalse($servicio->estaConfigurado());

        $resumen = $servicio->enviarATodos('Titulo', 'Cuerpo', 'https://ejemplo.test');
        $this->assertEquals(['enviados' => 0, 'caducados' => 0, 'fallidos' => 0], $resumen);

        // Y tampoco se ofrece el boton en la portada.
        $this->postJson(route('avisos.suscribir'), $this->datosDeSuscripcion())->assertStatus(503);
        DB::rollBack();
    }

    // ---------- Aislamiento ----------

    public function test_los_avisos_no_tocan_el_dinero_ni_los_socios(): void
    {
        Queue::fake();
        DB::beginTransaction();
        $cargos = Cargo::count();
        $sumaCargos = (float) Cargo::sum('monto');
        $pagos = Pago::count();
        $socios = Socio::count();

        $this->postJson(route('avisos.suscribir'), $this->datosDeSuscripcion())->assertOk();

        $this->assertEquals($cargos, Cargo::count());
        $this->assertEquals($sumaCargos, (float) Cargo::sum('monto'));
        $this->assertEquals($pagos, Pago::count());
        $this->assertEquals($socios, Socio::count(), 'Suscribirse no crea socios.');
        DB::rollBack();
    }

    public function test_la_portada_ofrece_activar_los_avisos(): void
    {
        $html = $this->get(route('home'))->assertOk()->getContent();

        $this->assertStringContainsString('Avisos de resultados', $html);
        $this->assertStringContainsString('sw.js', $html);
        $this->assertStringContainsString('manifest.json', $html, 'Hace falta para el iPhone.');
    }
}
