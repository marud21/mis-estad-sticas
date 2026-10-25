<?php

namespace Tests\Feature;

use App\Models\Cargo;
use App\Models\Equipo;
use App\Models\Pago;
use App\Models\Partido;
use App\Models\Sancion;
use App\Models\Socio;
use App\Models\Torneo;
use App\Models\User;
use App\Services\EstadisticasTorneoService;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class SancionesTest extends TestCase
{
    /** @return array{0:Torneo,1:Equipo,2:Equipo,3:Socio} */
    private function escenario(): array
    {
        $t = Torneo::create(['nombre' => 'Torneo sanciones '.uniqid(), 'fecha_inicio' => '2030-01-01']);
        $a = Equipo::create(['nombre' => 'SA '.uniqid(), 'torneo_id' => $t->id, 'categoria' => '50', 'estado' => 'activo']);
        $b = Equipo::create(['nombre' => 'SB '.uniqid(), 'torneo_id' => $t->id, 'categoria' => '50', 'estado' => 'activo']);

        $socio = Socio::create([
            'nombre_completo' => 'SANCIONADO '.uniqid(), 'numero_documento' => uniqid('s'),
            'fecha_nacimiento' => '1990-01-01', 'entidad_salud' => 'X', 'celular' => '300',
            'tipo_sangre' => 'O+', 'direccion_residencia' => 'X', 'posicion_juego' => 'Medio',
            'nivel_jugador' => 2, 'estado' => 'activo',
        ]);
        $socio->equipos()->attach($a->id);

        return [$t, $a, $b, $socio];
    }

    private function jugar(Torneo $t, Equipo $l, Equipo $v, string $fecha): Partido
    {
        return Partido::create([
            'torneo_id' => $t->id, 'equipo_local_id' => $l->id, 'equipo_visitante_id' => $v->id,
            'goles_local' => 1, 'goles_visitante' => 0,
            'fecha_hora' => $fecha, 'estado' => Partido::ESTADO_JUGADO,
        ]);
    }

    private function sancionar(Torneo $t, Equipo $e, Socio $s, int $fechas, string $desde): Sancion
    {
        return Sancion::create([
            'torneo_id' => $t->id, 'equipo_id' => $e->id, 'socio_id' => $s->id,
            'fechas' => $fechas, 'motivo' => 'Agresion', 'fecha_desde' => $desde,
        ]);
    }

    // ---------- Conteo automatico de fechas ----------

    public function test_las_fechas_cumplidas_se_cuentan_con_los_partidos_del_equipo(): void
    {
        DB::beginTransaction();
        [$t, $a, $b, $socio] = $this->escenario();
        $sancion = $this->sancionar($t, $a, $socio, 3, '2030-02-01');

        $this->assertEquals(0, $sancion->fechas_cumplidas, 'Sin partidos aun no cumplio nada.');
        $this->assertEquals(3, $sancion->fechas_restantes);
        $this->assertTrue($sancion->vigente);

        $this->jugar($t, $a, $b, '2030-02-05 10:00');
        $this->jugar($t, $b, $a, '2030-02-12 10:00');

        $sancion->refresh();
        $this->assertEquals(2, $sancion->fechas_cumplidas, 'Cuenta de local y de visitante.');
        $this->assertEquals(1, $sancion->fechas_restantes);
        $this->assertTrue($sancion->vigente);

        $this->jugar($t, $a, $b, '2030-02-19 10:00');
        $sancion->refresh();
        $this->assertEquals(0, $sancion->fechas_restantes);
        $this->assertFalse($sancion->vigente, 'Cumplidas las 3 fechas, deja de estar vigente.');

        DB::rollBack();
    }

    public function test_no_cuentan_los_partidos_anteriores_a_la_sancion(): void
    {
        DB::beginTransaction();
        [$t, $a, $b, $socio] = $this->escenario();
        $this->jugar($t, $a, $b, '2030-01-10 10:00');

        $sancion = $this->sancionar($t, $a, $socio, 2, '2030-02-01');

        $this->assertEquals(0, $sancion->fechas_cumplidas, 'Un partido previo no cuenta.');
        DB::rollBack();
    }

    public function test_no_cuentan_los_partidos_de_otro_equipo_ni_los_no_jugados(): void
    {
        DB::beginTransaction();
        [$t, $a, $b, $socio] = $this->escenario();
        $otro = Equipo::create(['nombre' => 'SC '.uniqid(), 'torneo_id' => $t->id, 'estado' => 'activo']);
        $sancion = $this->sancionar($t, $a, $socio, 3, '2030-02-01');

        // Partido entre otros dos equipos
        $this->jugar($t, $b, $otro, '2030-02-05 10:00');
        // Partido de su equipo pero aplazado
        Partido::create([
            'torneo_id' => $t->id, 'equipo_local_id' => $a->id, 'equipo_visitante_id' => $b->id,
            'fecha_hora' => '2030-02-06 10:00', 'estado' => Partido::ESTADO_APLAZADO,
        ]);

        $this->assertEquals(0, $sancion->fresh()->fechas_cumplidas);
        DB::rollBack();
    }

    public function test_el_scope_vigentes_filtra_las_ya_cumplidas(): void
    {
        DB::beginTransaction();
        [$t, $a, $b, $socio] = $this->escenario();
        $corta = $this->sancionar($t, $a, $socio, 1, '2030-02-01');
        $larga = $this->sancionar($t, $a, $socio, 5, '2030-02-01');

        $this->jugar($t, $a, $b, '2030-02-05 10:00');

        $vigentes = Sancion::where('torneo_id', $t->id)->vigentes()->pluck('id');
        $this->assertNotContains($corta->id, $vigentes->all(), 'La de 1 fecha ya se cumplio.');
        $this->assertContains($larga->id, $vigentes->all());
        DB::rollBack();
    }

    // ---------- La pagina publica ----------

    public function test_la_portada_muestra_solo_sanciones_vigentes(): void
    {
        DB::beginTransaction();
        [$t, $a, $b, $socio] = $this->escenario();
        $this->sancionar($t, $a, $socio, 4, '2030-02-01');

        $sancionados = app(EstadisticasTorneoService::class)->sancionados($t);

        $this->assertCount(1, $sancionados);
        $this->assertEquals($socio->nombre_completo, $sancionados[0]->jugador);
        $this->assertEquals(4, $sancionados[0]->fechas);
        $this->assertEquals(4, $sancionados[0]->restantes);
        $this->assertEquals('Agresion', $sancionados[0]->motivo);

        // Al cumplirla, desaparece de la pagina publica
        for ($i = 1; $i <= 4; $i++) {
            $this->jugar($t, $a, $b, '2030-03-0'.$i.' 10:00');
        }
        $this->assertTrue(app(EstadisticasTorneoService::class)->sancionados($t)->isEmpty());
        DB::rollBack();
    }

    public function test_la_seccion_de_sancionados_y_la_de_tarjetas_son_distintas(): void
    {
        DB::beginTransaction();
        [$t, $a, $b, $socio] = $this->escenario();
        $this->sancionar($t, $a, $socio, 2, '2030-02-01');

        $html = $this->get(route('home', ['torneo_id' => $t->id]))->getContent();

        $this->assertStringContainsString('Sancionados', $html);
        $this->assertStringContainsString('Tarjetas', $html);
        $this->assertStringContainsString('Agresion', $html, 'El motivo debe verse en la portada.');
        $this->assertStringContainsString('No hay jugadores con tarjetas', $html, 'Sin tarjetas, esa seccion va vacia.');
        DB::rollBack();
    }

    // ---------- Administracion ----------

    public function test_registra_una_sancion_por_http(): void
    {
        DB::beginTransaction();
        [$t, $a, $b, $socio] = $this->escenario();

        $this->actingAs(User::firstOrFail())
            ->post(route('sanciones.store'), [
                'torneo_id' => $t->id, 'equipo_id' => $a->id, 'socio_id' => $socio->id,
                'fechas' => 3, 'motivo' => 'Conducta antideportiva', 'fecha_desde' => '2030-02-01',
            ])
            ->assertRedirect();

        $this->assertEquals(1, Sancion::where('torneo_id', $t->id)->count());
        DB::rollBack();
    }

    public function test_valida_los_datos_de_la_sancion(): void
    {
        DB::beginTransaction();
        [$t, $a, $b, $socio] = $this->escenario();
        $base = ['torneo_id' => $t->id, 'equipo_id' => $a->id, 'socio_id' => $socio->id, 'fecha_desde' => '2030-02-01'];

        // Sin motivo
        $this->actingAs(User::firstOrFail())
            ->post(route('sanciones.store'), $base + ['fechas' => 2])
            ->assertSessionHasErrors('motivo');

        // Cero fechas no es una sancion
        $this->actingAs(User::firstOrFail())
            ->post(route('sanciones.store'), $base + ['fechas' => 0, 'motivo' => 'X'])
            ->assertSessionHasErrors('fechas');

        $this->assertEquals(0, Sancion::where('torneo_id', $t->id)->count());
        DB::rollBack();
    }

    public function test_elimina_una_sancion(): void
    {
        DB::beginTransaction();
        [$t, $a, $b, $socio] = $this->escenario();
        $sancion = $this->sancionar($t, $a, $socio, 2, '2030-02-01');

        $this->actingAs(User::firstOrFail())
            ->delete(route('sanciones.destroy', $sancion))
            ->assertRedirect();

        $this->assertNull(Sancion::find($sancion->id));
        DB::rollBack();
    }

    public function test_la_pantalla_de_sanciones_responde(): void
    {
        // Primero como visitante: la pantalla es privada.
        $this->get(route('sanciones.index'))->assertRedirect(route('login'));
        $this->actingAs(User::firstOrFail())->get(route('sanciones.index'))->assertOk();
    }

    // ---------- Aislamiento ----------

    public function test_sancionar_no_toca_el_dinero_ni_el_estado_del_socio(): void
    {
        DB::beginTransaction();
        [$t, $a, $b, $socio] = $this->escenario();
        $cargos = Cargo::count();
        $sumaCargos = (float) Cargo::sum('monto');
        $pagos = Pago::count();

        $this->actingAs(User::firstOrFail())->post(route('sanciones.store'), [
            'torneo_id' => $t->id, 'equipo_id' => $a->id, 'socio_id' => $socio->id,
            'fechas' => 3, 'motivo' => 'Prueba', 'fecha_desde' => '2030-02-01',
        ]);

        $this->assertEquals($cargos, Cargo::count(), 'Una sancion deportiva no genera cargos.');
        $this->assertEquals($sumaCargos, (float) Cargo::sum('monto'));
        $this->assertEquals($pagos, Pago::count());
        $this->assertEquals('activo', $socio->fresh()->estado, 'No cambia el estado del socio.');
        DB::rollBack();
    }
}
