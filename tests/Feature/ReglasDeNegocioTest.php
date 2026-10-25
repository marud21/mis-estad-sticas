<?php

namespace Tests\Feature;

use App\Models\Cargo;
use App\Models\CierreCaja;
use App\Models\Configuracion;
use App\Models\Equipo;
use App\Models\IncidenciaPartido;
use App\Models\Pago;
use App\Models\Partido;
use App\Models\Socio;
use App\Models\TipoCargo;
use App\Models\Torneo;
use App\Models\User;
use App\Services\CargoService;
use App\Services\CuotaModeradaService;
use App\Services\EquipoService;
use App\Services\RegistroPartidoService;
use App\Services\ReporteService;
use App\Services\TablaPosicionesService;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Las reglas de negocio de cada modulo, en un solo lugar. Son las que
 * importan si algo se rompe: el dinero, los estados y la competencia.
 */
class ReglasDeNegocioTest extends TestCase
{
    private function socio(string $nombre, ?Equipo $equipo = null, string $estado = 'activo'): Socio
    {
        $s = Socio::create([
            'nombre_completo' => $nombre, 'numero_documento' => uniqid('r'),
            'fecha_nacimiento' => '1990-01-01', 'entidad_salud' => 'X', 'celular' => '300',
            'tipo_sangre' => 'O+', 'direccion_residencia' => 'X', 'posicion_juego' => 'Medio',
            'nivel_jugador' => 2, 'estado' => $estado,
        ]);
        if ($equipo) {
            $s->equipos()->attach($equipo->id);
        }

        return $s;
    }

    private function torneo(): array
    {
        $t = Torneo::create(['nombre' => 'T '.uniqid(), 'fecha_inicio' => '2030-01-01']);
        $a = Equipo::create(['nombre' => 'RA '.uniqid(), 'torneo_id' => $t->id, 'estado' => 'activo']);
        $b = Equipo::create(['nombre' => 'RB '.uniqid(), 'torneo_id' => $t->id, 'estado' => 'activo']);

        return [$t, $a, $b];
    }

    // ---------- FINANZAS: cobros ----------

    public function test_al_suspendido_se_le_cobra_el_porcentaje_configurado(): void
    {
        DB::beginTransaction();
        [$t, $a] = $this->torneo();
        $activo = $this->socio('R ACTIVO', $a);
        $suspendido = $this->socio('R SUSPENDIDO', $a, Socio::ESTADO_SUSPENDIDO);
        $tipo = TipoCargo::where('nombre', 'Mensualidad')->firstOrFail();

        app(CargoService::class)->aplicarMasivo($tipo, '2030-03-01', 100000, 'equipo', $a->id);

        $this->assertEquals(100000, $activo->fresh()->cargos()->firstOrFail()->monto);
        $esperado = round(100000 * $tipo->porcentaje_suspendido / 100, 2);
        $this->assertEquals($esperado, $suspendido->fresh()->cargos()->firstOrFail()->monto);
        DB::rollBack();
    }

    public function test_el_cobro_masivo_no_duplica_si_se_repite(): void
    {
        DB::beginTransaction();
        [$t, $a] = $this->torneo();
        $socio = $this->socio('R NODUPLICA', $a);
        $tipo = TipoCargo::where('nombre', 'Mensualidad')->firstOrFail();

        app(CargoService::class)->aplicarMasivo($tipo, '2030-03-01', null, 'equipo', $a->id);
        app(CargoService::class)->aplicarMasivo($tipo, '2030-03-01', null, 'equipo', $a->id);

        $this->assertEquals(1, $socio->fresh()->cargos()->where('fecha', '2030-03-01')->count());
        DB::rollBack();
    }

    public function test_un_socio_con_dos_equipos_recibe_una_mensualidad_por_cada_uno(): void
    {
        DB::beginTransaction();
        [$t, $a, $b] = $this->torneo();
        $socio = $this->socio('R MULTIEQUIPO', $a);
        $socio->equipos()->attach($b->id);
        $tipo = TipoCargo::where('nombre', 'Mensualidad')->firstOrFail();

        app(CargoService::class)->aplicarMasivo($tipo, '2030-04-01', null, 'todos');

        $cargos = $socio->fresh()->cargos()->where('fecha', '2030-04-01')->get();
        $this->assertCount(2, $cargos, 'Un cargo por cada equipo.');
        $this->assertEqualsCanonicalizing([$a->id, $b->id], $cargos->pluck('equipo_id')->all());
        DB::rollBack();
    }

    // ---------- FINANZAS: cuota moderada ----------

    public function test_la_cuota_moderada_queda_fija_y_nunca_supera_la_deuda(): void
    {
        DB::beginTransaction();
        [$t, $a] = $this->torneo();
        $socio = $this->socio('R CUOTA', $a);
        $tipo = TipoCargo::firstOrFail();
        $socio->cargos()->create(['tipo_cargo_id' => $tipo->id, 'monto' => 100000, 'fecha' => '2030-01-01']);

        Configuracion::guardar(Configuracion::PORCENTAJE_CUOTA_MODERADA, '25');
        app(CuotaModeradaService::class)->recalcularTodas();

        $socio->refresh();
        $this->assertEquals(25000, $socio->cuota_moderada, '25% de 100.000.');

        // Abona y la cuota NO baja (ese era el error original)
        $socio->pagos()->create(['valor' => 25000, 'fecha' => '2030-01-05', 'tipo' => 'efectivo']);
        $this->assertEquals(25000, $socio->fresh()->cuota_moderada_vigente);

        // Pero nunca pide mas de lo que realmente se debe
        $socio->pagos()->create(['valor' => 65000, 'fecha' => '2030-01-06', 'tipo' => 'efectivo']);
        $this->assertEquals(10000, $socio->fresh()->cuota_moderada_vigente, 'Se limita a la deuda restante.');
        DB::rollBack();
    }

    // ---------- SOCIOS: estados ----------

    public function test_al_excluido_no_se_le_cobra_pero_conserva_su_deuda(): void
    {
        DB::beginTransaction();
        [$t, $a] = $this->torneo();
        $socio = $this->socio('R EXCLUIDO', $a);
        $socio->cargos()->create(['tipo_cargo_id' => TipoCargo::firstOrFail()->id, 'monto' => 50000, 'fecha' => '2030-01-01']);
        $deuda = $socio->deuda_total;

        $socio->update(['estado' => Socio::ESTADO_EXCLUIDO]);
        app(CargoService::class)->aplicarMasivo(TipoCargo::where('nombre', 'Mensualidad')->firstOrFail(), '2030-05-01', null, 'equipo', $a->id);

        $this->assertEquals(0, $socio->fresh()->cargos()->where('fecha', '2030-05-01')->count());
        $this->assertEquals($deuda, $socio->fresh()->deuda_total, 'La deuda previa se conserva.');
        DB::rollBack();
    }

    public function test_quedarse_sin_equipo_retira_al_socio_pero_no_si_le_queda_otro(): void
    {
        DB::beginTransaction();
        [$t, $a, $b] = $this->torneo();
        $socio = $this->socio('R DOSEQUIPOS', $a);
        $socio->equipos()->attach($b->id);
        $servicio = app(EquipoService::class);

        $servicio->quitarSocio($a, $socio);
        $this->assertEquals('activo', $socio->fresh()->estado, 'Le queda un equipo: sigue activo.');

        $servicio->quitarSocio($b, $socio);
        $this->assertEquals('retirado', $socio->fresh()->estado, 'Sin equipos: retirado.');

        $servicio->agregarSocio($a, $socio);
        $this->assertEquals('activo', $socio->fresh()->estado, 'Vuelve a tener equipo: se reactiva.');
        DB::rollBack();
    }

    public function test_el_suspendido_y_el_excluido_salen_en_la_planilla_de_juego(): void
    {
        DB::beginTransaction();
        [$t, $a, $b] = $this->torneo();
        $this->socio('R EN PLANILLA ACTIVO', $a);
        $this->socio('R EN PLANILLA SUSP', $a, Socio::ESTADO_SUSPENDIDO);
        $this->socio('R EN PLANILLA EXCL', $a, Socio::ESTADO_EXCLUIDO);
        $this->socio('R EN PLANILLA RETIR', $a, Socio::ESTADO_RETIRADO);

        $enPlanilla = $a->socios()->whereIn('estado', Socio::ESTADOS_EN_PLANILLA)->pluck('nombre_completo');

        $this->assertContains('R EN PLANILLA ACTIVO', $enPlanilla->all());
        $this->assertContains('R EN PLANILLA SUSP', $enPlanilla->all());
        $this->assertContains('R EN PLANILLA EXCL', $enPlanilla->all());
        $this->assertNotContains('R EN PLANILLA RETIR', $enPlanilla->all());
        DB::rollBack();
    }

    // ---------- COMPETENCIA ----------

    public function test_la_tabla_ordena_por_puntos_diferencia_y_goles(): void
    {
        DB::beginTransaction();
        [$t, $a, $b] = $this->torneo();
        $c = Equipo::create(['nombre' => 'RC '.uniqid(), 'torneo_id' => $t->id, 'estado' => 'activo']);

        $jugar = fn ($l, $v, $gl, $gv) => Partido::create([
            'torneo_id' => $t->id, 'equipo_local_id' => $l->id, 'equipo_visitante_id' => $v->id,
            'goles_local' => $gl, 'goles_visitante' => $gv,
            'fecha_hora' => '2030-02-01 10:00', 'estado' => Partido::ESTADO_JUGADO,
        ]);

        $jugar($a, $b, 5, 0);   // A: 3 pts, DG +5
        $jugar($c, $b, 1, 0);   // C: 3 pts, DG +1

        $tabla = app(TablaPosicionesService::class)->calcular($t);
        $this->assertEquals($a->nombre, $tabla[0]['equipo'], 'Mejor diferencia primero.');
        $this->assertEquals(3, $tabla[0]['pts']);
        DB::rollBack();
    }

    public function test_solo_los_partidos_jugados_cuentan_en_la_tabla(): void
    {
        DB::beginTransaction();
        [$t, $a, $b] = $this->torneo();
        Partido::create([
            'torneo_id' => $t->id, 'equipo_local_id' => $a->id, 'equipo_visitante_id' => $b->id,
            'goles_local' => 9, 'goles_visitante' => 0,
            'fecha_hora' => '2030-02-01 10:00', 'estado' => Partido::ESTADO_APLAZADO,
        ]);

        $this->assertEquals(0, app(TablaPosicionesService::class)->calcular($t)->firstWhere('equipo', $a->nombre)['pj']);
        DB::rollBack();
    }

    public function test_el_ajuste_de_puntos_pesa_en_el_orden(): void
    {
        DB::beginTransaction();
        [$t, $a, $b] = $this->torneo();
        Partido::create([
            'torneo_id' => $t->id, 'equipo_local_id' => $a->id, 'equipo_visitante_id' => $b->id,
            'goles_local' => 1, 'goles_visitante' => 0,
            'fecha_hora' => '2030-02-01 10:00', 'estado' => Partido::ESTADO_JUGADO,
        ]);
        \App\Models\AjustePuntos::create([
            'torneo_id' => $t->id, 'equipo_id' => $b->id,
            'puntos' => 5, 'motivo' => 'Bonificacion', 'fecha' => '2030-02-05',
        ]);

        $this->assertEquals($b->nombre, app(TablaPosicionesService::class)->calcular($t)[0]['equipo']);
        DB::rollBack();
    }

    public function test_el_marcador_sale_de_los_goles_y_el_autogol_suma_al_rival(): void
    {
        DB::beginTransaction();
        [$t, $a, $b] = $this->torneo();
        $jugadorA = $this->socio('R GOLEADOR', $a);
        $partido = Partido::create([
            'torneo_id' => $t->id, 'equipo_local_id' => $a->id, 'equipo_visitante_id' => $b->id,
            'fecha_hora' => '2030-02-01 10:00', 'estado' => Partido::ESTADO_PROGRAMADO,
        ]);

        app(RegistroPartidoService::class)->registrar($partido, [
            ['socio_id' => $jugadorA->id, 'equipo_id' => $a->id, 'tipo' => IncidenciaPartido::TIPO_GOL],
            ['socio_id' => $jugadorA->id, 'equipo_id' => $a->id, 'tipo' => IncidenciaPartido::TIPO_AUTOGOL],
        ]);

        $partido->refresh();
        $this->assertEquals(1, $partido->goles_local);
        $this->assertEquals(1, $partido->goles_visitante, 'El autogol suma al rival.');
        $this->assertEquals(Partido::ESTADO_JUGADO, $partido->estado);
        DB::rollBack();
    }

    public function test_corregir_un_resultado_no_duplica_cargos_ni_borra_los_pagados(): void
    {
        DB::beginTransaction();
        [$t, $a, $b] = $this->torneo();
        $socio = $this->socio('R TARJETEADO', $a);
        $partido = Partido::create([
            'torneo_id' => $t->id, 'equipo_local_id' => $a->id, 'equipo_visitante_id' => $b->id,
            'fecha_hora' => '2030-02-01 10:00', 'estado' => Partido::ESTADO_PROGRAMADO,
        ]);
        $servicio = app(RegistroPartidoService::class);

        $servicio->registrar($partido, [
            ['socio_id' => $socio->id, 'equipo_id' => $a->id, 'tipo' => IncidenciaPartido::TIPO_AMARILLA],
            ['socio_id' => $socio->id, 'equipo_id' => $a->id, 'tipo' => IncidenciaPartido::TIPO_ROJA],
        ]);
        $this->assertEquals(2, $socio->fresh()->cargos()->count());

        // Se paga la roja y luego se corrige el partido quitando todo
        $roja = $socio->fresh()->cargos()->orderByDesc('monto')->firstOrFail();
        Pago::create(['socio_id' => $socio->id, 'cargo_id' => $roja->id, 'valor' => 5000, 'fecha' => '2030-02-02', 'tipo' => 'efectivo']);

        $servicio->registrar($partido, []);

        $this->assertNotNull(Cargo::find($roja->id), 'Un cargo con pagos no se borra.');
        $this->assertEquals(1, $socio->fresh()->cargos()->count(), 'Solo queda el que tenia pago.');
        DB::rollBack();
    }

    public function test_la_planilla_de_cobro_resalta_las_tarjetas_del_ultimo_partido(): void
    {
        DB::beginTransaction();
        [$t, $a, $b] = $this->torneo();
        $conTarjeta = $this->socio('R CON TARJETA', $a);
        $sinTarjeta = $this->socio('R SIN TARJETA', $a);
        $partido = Partido::create([
            'torneo_id' => $t->id, 'equipo_local_id' => $a->id, 'equipo_visitante_id' => $b->id,
            'fecha_hora' => '2030-02-01 10:00', 'estado' => Partido::ESTADO_PROGRAMADO,
        ]);
        app(RegistroPartidoService::class)->registrar($partido, [
            ['socio_id' => $conTarjeta->id, 'equipo_id' => $a->id, 'tipo' => IncidenciaPartido::TIPO_AMARILLA],
        ]);

        $resultado = app(ReporteService::class)->tarjetasDelUltimoPartido($a->fresh());
        $this->assertEquals(1, $resultado['tarjetas'][$conTarjeta->id]['amarillas']);
        $this->assertNull($resultado['tarjetas']->get($sinTarjeta->id));
        DB::rollBack();
    }

    // ---------- CIERRE DE CAJA ----------

    public function test_el_cierre_se_calcula_de_los_pagos_y_se_puede_anular(): void
    {
        DB::beginTransaction();
        [$t, $a] = $this->torneo();
        $socio = $this->socio('R CAJA', $a);
        $socio->pagos()->create(['valor' => 30000, 'fecha' => '2030-09-20', 'tipo' => 'efectivo']);
        $socio->pagos()->create(['valor' => 20000, 'fecha' => '2030-09-20', 'tipo' => 'transferencia']);

        $this->actingAs(User::firstOrFail())
            ->post(route('cierre-caja.store'), ['fecha' => '2030-09-20'])
            ->assertRedirect();

        $cierre = CierreCaja::whereDate('fecha', '2030-09-20')->firstOrFail();
        $this->assertEquals(30000, $cierre->total_efectivo);
        $this->assertEquals(20000, $cierre->total_transferencia);

        // No deja dos cierres activos el mismo dia
        $this->actingAs(User::firstOrFail())
            ->post(route('cierre-caja.store'), ['fecha' => '2030-09-20'])
            ->assertSessionHasErrors('fecha');

        // Se anula y entonces si deja rehacerlo
        $this->actingAs(User::firstOrFail())
            ->patch(route('cierre-caja.anular', $cierre), ['motivo' => 'Se cerro antes de tiempo'])
            ->assertRedirect();
        $this->assertTrue($cierre->fresh()->anulado);

        $this->actingAs(User::firstOrFail())
            ->post(route('cierre-caja.store'), ['fecha' => '2030-09-20'])
            ->assertSessionHasNoErrors();
        DB::rollBack();
    }

    // ---------- SEGURIDAD ----------

    public function test_la_sesion_vencida_lleva_al_login_y_no_a_la_pantalla_419(): void
    {
        $peticion = \Illuminate\Http\Request::create('/socios', 'POST');
        $peticion->setLaravelSession($this->app['session.store']);

        $respuesta = $this->app[\Illuminate\Contracts\Debug\ExceptionHandler::class]
            ->render($peticion, new \Symfony\Component\HttpKernel\Exception\HttpException(419, 'CSRF'));

        $this->assertInstanceOf(\Illuminate\Http\RedirectResponse::class, $respuesta);
        $this->assertEquals(route('login'), $respuesta->getTargetUrl());
    }

    public function test_un_404_sigue_siendo_404(): void
    {
        $respuesta = $this->app[\Illuminate\Contracts\Debug\ExceptionHandler::class]->render(
            \Illuminate\Http\Request::create('/no-existe', 'GET'),
            new \Symfony\Component\HttpKernel\Exception\NotFoundHttpException('no existe')
        );

        $this->assertEquals(404, $respuesta->getStatusCode());
    }
}
