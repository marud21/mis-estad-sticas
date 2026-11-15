<?php

namespace Tests\Feature;

use App\Jobs\EnviarAvisoPush;
use App\Models\Pago;
use App\Models\Socio;
use App\Models\SuscripcionPush;
use App\Models\User;
use App\Services\NotificacionPushService;
use App\Services\SocioInactivoService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

/**
 * El aviso semanal de socios sin pagos recientes.
 *
 * Lo que mas se cuida aqui es a quien le llega. Los avisos de resultados
 * son publicos y los acepta cualquiera que entre a la pagina; este dice
 * cuanta gente debe y cuanto, asi que no puede salir por ese mismo canal.
 */
class AvisoSociosSinPagoTest extends TestCase
{
    private function admin(): User
    {
        return User::where('role', User::ROLE_ADMIN)->firstOrFail();
    }

    /** Una suscripcion de mentira, con claves del largo que exige el cifrado. */
    private function suscripcion(?int $userId = null): SuscripcionPush
    {
        $endpoint = 'https://ejemplo.test/push/'.uniqid();

        return SuscripcionPush::create([
            'endpoint' => $endpoint,
            'endpoint_huella' => SuscripcionPush::huella($endpoint),
            'clave_p256dh' => str_repeat('A', 87),
            'clave_auth' => str_repeat('B', 22),
            'navegador' => 'Pruebas',
            'user_id' => $userId,
        ]);
    }

    private function socioAtrasado(int $meses = 9): Socio
    {
        $socio = Socio::create([
            'nombre_completo' => 'SOCIO AVISO '.uniqid(),
            'numero_documento' => uniqid('a'),
            'fecha_nacimiento' => '1990-01-01',
            'fecha_ingreso' => '2020-01-01',
            'entidad_salud' => 'X', 'celular' => '300',
            'tipo_sangre' => 'O+', 'direccion_residencia' => 'X',
            'posicion_juego' => 'Medio', 'nivel_jugador' => 2,
            'estado' => Socio::ESTADO_ACTIVO,
        ]);

        Pago::create([
            'socio_id' => $socio->id, 'valor' => 1000,
            'fecha' => Carbon::today()->subMonths($meses)->toDateString(),
            'tipo' => 'efectivo',
        ]);

        return $socio;
    }

    // ---------- A quien le llega ----------

    public function test_el_aviso_interno_no_sale_hacia_los_suscritos_del_publico(): void
    {
        DB::beginTransaction();
        $delPublico = $this->suscripcion(null);
        $deLaAdmin = $this->suscripcion($this->admin()->id);

        $paraAdmin = SuscripcionPush::deLaAdministracion()->pluck('id');

        $this->assertTrue(
            $paraAdmin->contains($deLaAdmin->id),
            'Al navegador de la administracion si le llega.',
        );
        $this->assertFalse(
            $paraAdmin->contains($delPublico->id),
            'Al visitante de la pagina publica no puede llegarle: el aviso dice quien debe y cuanto.',
        );

        DB::rollBack();
    }

    public function test_suscribirse_con_sesion_marca_la_suscripcion_como_de_la_administracion(): void
    {
        DB::beginTransaction();
        $admin = $this->admin();
        $endpoint = 'https://ejemplo.test/push/'.uniqid();

        $this->actingAs($admin)->postJson(route('avisos.suscribir'), [
            'endpoint' => $endpoint,
            'clave_p256dh' => str_repeat('A', 87),
            'clave_auth' => str_repeat('B', 22),
        ])->assertOk();

        $guardada = SuscripcionPush::where('endpoint_huella', SuscripcionPush::huella($endpoint))->firstOrFail();
        $this->assertSame($admin->id, $guardada->user_id);

        DB::rollBack();
    }

    public function test_suscribirse_sin_sesion_sigue_siendo_anonimo(): void
    {
        DB::beginTransaction();
        $endpoint = 'https://ejemplo.test/push/'.uniqid();

        $this->postJson(route('avisos.suscribir'), [
            'endpoint' => $endpoint,
            'clave_p256dh' => str_repeat('A', 87),
            'clave_auth' => str_repeat('B', 22),
        ])->assertOk();

        $guardada = SuscripcionPush::where('endpoint_huella', SuscripcionPush::huella($endpoint))->firstOrFail();
        $this->assertNull(
            $guardada->user_id,
            'Los avisos de resultados se piden sin cuenta: eso no puede cambiar.',
        );

        DB::rollBack();
    }

    public function test_volver_a_suscribirse_sin_sesion_no_le_quita_el_dueño_a_la_suscripcion(): void
    {
        DB::beginTransaction();
        $admin = $this->admin();
        $suscripcion = $this->suscripcion($admin->id);

        // El mismo navegador, ahora sin sesion (p. ej. abrio la portada).
        app(NotificacionPushService::class)->suscribir([
            'endpoint' => $suscripcion->endpoint,
            'clave_p256dh' => str_repeat('C', 87),
            'clave_auth' => str_repeat('D', 22),
        ], 'Pruebas', null);

        $this->assertSame(
            $admin->id,
            $suscripcion->fresh()->user_id,
            'Entrar una vez sin sesion no puede dejarlo sin sus avisos internos.',
        );

        DB::rollBack();
    }

    // ---------- El comando ----------

    public function test_el_comando_encola_el_aviso_solo_para_la_administracion(): void
    {
        DB::beginTransaction();
        Queue::fake();
        $this->socioAtrasado();

        $this->artisan('avisos:socios-sin-pago')->assertSuccessful();

        Queue::assertPushed(EnviarAvisoPush::class, function (EnviarAvisoPush $job) {
            $reflejo = new \ReflectionClass($job);
            $propiedad = $reflejo->getProperty('soloAdministracion');
            $propiedad->setAccessible(true);

            return $propiedad->getValue($job) === true;
        });

        DB::rollBack();
    }

    public function test_el_comando_no_manda_nada_si_no_hay_a_quien_revisar(): void
    {
        Queue::fake();

        // Con un corte de 600 meses no queda nadie por revisar.
        $this->artisan('avisos:socios-sin-pago --meses=600')
            ->expectsOutputToContain('No se manda nada')
            ->assertSuccessful();

        Queue::assertNothingPushed();
    }

    public function test_el_ensayo_dice_que_mandaria_sin_mandar_nada(): void
    {
        DB::beginTransaction();
        Queue::fake();
        $this->socioAtrasado();

        $this->artisan('avisos:socios-sin-pago --dry-run')
            ->expectsOutputToContain('Se mandaria')
            ->assertSuccessful();

        Queue::assertNothingPushed();

        DB::rollBack();
    }

    public function test_el_aviso_dice_cuantos_hay_y_cuanto_deben(): void
    {
        DB::beginTransaction();
        Queue::fake();
        $this->socioAtrasado();

        $resumen = app(SocioInactivoService::class)->resumen();

        $this->artisan('avisos:socios-sin-pago')->assertSuccessful();

        Queue::assertPushed(EnviarAvisoPush::class, function (EnviarAvisoPush $job) use ($resumen) {
            $reflejo = new \ReflectionClass($job);
            $leer = function (string $nombre) use ($reflejo, $job) {
                $p = $reflejo->getProperty($nombre);
                $p->setAccessible(true);

                return $p->getValue($job);
            };

            return str_contains($leer('titulo'), (string) $resumen['total'])
                && str_contains($leer('cuerpo'), number_format($resumen['deuda'], 0, ',', '.'))
                && str_contains($leer('url'), 'socios-inactivos');
        });

        DB::rollBack();
    }

    public function test_el_comando_no_cambia_ningun_dato(): void
    {
        DB::beginTransaction();
        Queue::fake();
        $socio = $this->socioAtrasado();

        $antes = [
            'socios' => Socio::count(),
            'pagos' => Pago::count(),
            'estado' => $socio->estado,
            'suscripciones' => SuscripcionPush::count(),
        ];

        $this->artisan('avisos:socios-sin-pago')->assertSuccessful();

        $this->assertSame($antes['socios'], Socio::count());
        $this->assertSame($antes['pagos'], Pago::count());
        $this->assertSame($antes['estado'], $socio->fresh()->estado);
        $this->assertSame($antes['suscripciones'], SuscripcionPush::count());

        DB::rollBack();
    }

    // ---------- La programacion ----------

    public function test_el_aviso_queda_programado_una_vez_por_semana(): void
    {
        $programados = collect(app(\Illuminate\Console\Scheduling\Schedule::class)->events())
            ->filter(fn ($e) => str_contains($e->command ?? '', 'avisos:socios-sin-pago'));

        $this->assertCount(1, $programados, 'Debe estar programado, y una sola vez.');
        $this->assertSame(
            '0 8 * * 1',
            $programados->first()->expression,
            'Los lunes a las 8 de la mañana.',
        );
    }

    // ---------- El boton para activarlo ----------

    public function test_la_pantalla_ofrece_activar_el_aviso(): void
    {
        $html = $this->actingAs($this->admin())
            ->get(route('socios-inactivos.index'))
            ->assertOk()
            ->getContent();

        // Si no hay claves VAPID configuradas el boton no se dibuja, y eso
        // es correcto: sin claves no se puede avisar nada.
        if (! app(NotificacionPushService::class)->estaConfigurado()) {
            $this->markTestSkipped('No hay claves VAPID configuradas en este entorno.');
        }

        $this->assertStringContainsString('Avisarme de los socios sin pagos', $html);
        $this->assertStringContainsString('cada lunes', $html);
        $this->assertStringContainsString('caja-avisos', $html);
    }
}
