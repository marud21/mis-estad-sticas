<?php

namespace Tests\Feature;

use App\Models\Cargo;
use App\Models\Equipo;
use App\Models\IncidenciaPartido;
use App\Models\Partido;
use App\Models\Socio;
use App\Models\TipoCargo;
use App\Models\Torneo;
use App\Models\User;
use App\Services\RegistroPartidoService;
use App\Services\TablaPosicionesService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class WalkoverTest extends TestCase
{
    private function socio(string $nombre, Equipo $equipo): Socio
    {
        $s = Socio::create([
            'nombre_completo' => $nombre, 'numero_documento' => uniqid('w'),
            'fecha_nacimiento' => '1990-01-01', 'entidad_salud' => 'X', 'celular' => '300',
            'tipo_sangre' => 'O+', 'direccion_residencia' => 'X', 'posicion_juego' => 'Medio',
            'nivel_jugador' => 2, 'estado' => 'activo',
        ]);
        $s->equipos()->attach($equipo->id);

        return $s;
    }

    /** @return array{0:Torneo,1:Equipo,2:Equipo} */
    private function escenario(): array
    {
        $t = Torneo::create(['nombre' => 'Torneo WO '.uniqid(), 'fecha_inicio' => '2030-01-01']);
        $a = Equipo::create(['nombre' => 'WA '.uniqid(), 'torneo_id' => $t->id, 'categoria' => '50', 'estado' => 'activo']);
        $b = Equipo::create(['nombre' => 'WB '.uniqid(), 'torneo_id' => $t->id, 'categoria' => '50', 'estado' => 'activo']);

        return [$t, $a, $b];
    }

    private function partido(Torneo $t, Equipo $l, Equipo $v): Partido
    {
        return Partido::create([
            'torneo_id' => $t->id, 'equipo_local_id' => $l->id, 'equipo_visitante_id' => $v->id,
            'fecha_hora' => '2030-02-01 10:00', 'estado' => Partido::ESTADO_PROGRAMADO, 'fase' => 'Jornada 1',
        ]);
    }

    // ---------- Guardar el walkover ----------

    public function test_registra_el_walkover_con_el_ausente_y_el_marcador(): void
    {
        DB::beginTransaction();
        [$t, $a, $b] = $this->escenario();
        $partido = $this->partido($t, $a, $b);

        app(RegistroPartidoService::class)->registrarWalkover($partido, $b->id, 3, 0);

        $partido->refresh();
        $this->assertEquals($b->id, $partido->equipo_ausente_id);
        $this->assertTrue($partido->es_walkover);
        $this->assertEquals(3, $partido->goles_local);
        $this->assertEquals(0, $partido->goles_visitante);
        $this->assertEquals(Partido::ESTADO_JUGADO, $partido->estado, 'Un walkover es un partido jugado.');
        DB::rollBack();
    }

    public function test_el_walkover_no_genera_ningun_cargo(): void
    {
        DB::beginTransaction();
        [$t, $a, $b] = $this->escenario();
        $this->socio('W JUGADOR', $a);
        $partido = $this->partido($t, $a, $b);
        $cargos = Cargo::count();

        app(RegistroPartidoService::class)->registrarWalkover($partido, $b->id, 3, 0);

        $this->assertEquals($cargos, Cargo::count(), 'Nadie jugo, nadie puede recibir una tarjeta.');
        $this->assertEquals(0, $partido->incidencias()->count());
        DB::rollBack();
    }

    public function test_marcar_walkover_borra_el_registro_anterior_del_partido(): void
    {
        DB::beginTransaction();
        [$t, $a, $b] = $this->escenario();
        $jugador = $this->socio('W CON TARJETA', $a);
        TipoCargo::firstOrCreate(['nombre' => 'Amarillas'], ['monto_default' => 5000, 'es_recurrente' => false, 'porcentaje_suspendido' => 100]);
        $partido = $this->partido($t, $a, $b);
        $servicio = app(RegistroPartidoService::class);

        // Primero se carga normal, con una amarilla que genera cargo.
        $servicio->registrar($partido, [
            ['socio_id' => $jugador->id, 'equipo_id' => $a->id, 'tipo' => IncidenciaPartido::TIPO_AMARILLA],
        ]);
        $this->assertEquals(1, $partido->fresh()->incidencias()->count());
        $cargosAntes = Cargo::where('socio_id', $jugador->id)->count();
        $this->assertEquals(1, $cargosAntes);

        // Luego se corrige: en realidad fue walkover.
        $servicio->registrarWalkover($partido->fresh(), $b->id, 3, 0);

        $this->assertEquals(0, $partido->fresh()->incidencias()->count());
        $this->assertEquals(0, Cargo::where('socio_id', $jugador->id)->count(), 'El cargo de la tarjeta se retira.');
        DB::rollBack();
    }

    public function test_el_walkover_quita_la_figura_del_partido(): void
    {
        DB::beginTransaction();
        [$t, $a, $b] = $this->escenario();
        $figura = $this->socio('W FIGURA', $a);
        $partido = $this->partido($t, $a, $b);
        $servicio = app(RegistroPartidoService::class);

        $servicio->registrar($partido, [], $figura->id);
        $this->assertEquals($figura->id, $partido->fresh()->jugador_partido_id);

        $servicio->registrarWalkover($partido->fresh(), $b->id, 3, 0);

        $this->assertNull($partido->fresh()->jugador_partido_id, 'Nadie jugo, no puede haber figura.');
        DB::rollBack();
    }

    public function test_registrar_normal_despues_quita_la_marca_de_walkover(): void
    {
        DB::beginTransaction();
        [$t, $a, $b] = $this->escenario();
        $jugador = $this->socio('W JUGADOR DOS', $a);
        $partido = $this->partido($t, $a, $b);
        $servicio = app(RegistroPartidoService::class);

        $servicio->registrarWalkover($partido, $b->id, 3, 0);
        $this->assertTrue($partido->fresh()->es_walkover);

        // Se corrige: en realidad si se jugo.
        $servicio->registrar($partido->fresh(), [
            ['socio_id' => $jugador->id, 'equipo_id' => $a->id, 'tipo' => IncidenciaPartido::TIPO_GOL],
        ]);

        $partido->refresh();
        $this->assertFalse($partido->es_walkover, 'Deja de ser walkover.');
        $this->assertNull($partido->equipo_ausente_id);
        $this->assertEquals(1, $partido->goles_local, 'El marcador vuelve a salir de los goles.');
        DB::rollBack();
    }

    public function test_el_walkover_cuenta_en_la_tabla_de_posiciones(): void
    {
        DB::beginTransaction();
        [$t, $a, $b] = $this->escenario();

        app(RegistroPartidoService::class)->registrarWalkover($this->partido($t, $a, $b), $b->id, 3, 0);

        $tabla = app(TablaPosicionesService::class)->calcular($t);
        $local = $tabla->firstWhere('equipo_id', $a->id);
        $ausente = $tabla->firstWhere('equipo_id', $b->id);

        $this->assertEquals(3, $local['pts'], 'El que se presento gana los 3 puntos.');
        $this->assertEquals(3, $local['gf']);
        $this->assertEquals(0, $ausente['pts']);
        $this->assertEquals(3, $ausente['gc']);
        DB::rollBack();
    }

    public function test_avisa_del_resultado_igual_que_un_partido_normal(): void
    {
        Queue::fake();
        DB::beginTransaction();
        [$t, $a, $b] = $this->escenario();

        app(RegistroPartidoService::class)->registrarWalkover($this->partido($t, $a, $b), $b->id, 3, 0);

        Queue::assertPushed(\App\Jobs\EnviarAvisoPush::class, 1);
        DB::rollBack();
    }

    // ---------- Validacion ----------

    public function test_exige_el_ausente_y_el_marcador_si_se_marca_walkover(): void
    {
        DB::beginTransaction();
        [$t, $a, $b] = $this->escenario();
        $partido = $this->partido($t, $a, $b);

        $this->actingAs(User::firstOrFail())
            ->post(route('partidos.registro.guardar', $partido), ['walkover' => 1])
            ->assertSessionHasErrors(['equipo_ausente_id', 'goles_local', 'goles_visitante']);

        $this->assertEquals(Partido::ESTADO_PROGRAMADO, $partido->fresh()->estado, 'No se guarda nada a medias.');
        DB::rollBack();
    }

    public function test_el_ausente_debe_ser_uno_de_los_dos_equipos(): void
    {
        DB::beginTransaction();
        [$t, $a, $b] = $this->escenario();
        $ajeno = Equipo::create(['nombre' => 'WC '.uniqid(), 'torneo_id' => $t->id, 'estado' => 'activo']);
        $partido = $this->partido($t, $a, $b);

        $this->actingAs(User::firstOrFail())
            ->post(route('partidos.registro.guardar', $partido), [
                'walkover' => 1, 'equipo_ausente_id' => $ajeno->id,
                'goles_local' => 3, 'goles_visitante' => 0,
            ])
            ->assertSessionHasErrors('equipo_ausente_id');

        $this->assertNull($partido->fresh()->equipo_ausente_id);
        DB::rollBack();
    }

    public function test_registra_el_walkover_por_http(): void
    {
        DB::beginTransaction();
        [$t, $a, $b] = $this->escenario();
        $partido = $this->partido($t, $a, $b);

        $this->actingAs(User::firstOrFail())
            ->post(route('partidos.registro.guardar', $partido), [
                'walkover' => 1, 'equipo_ausente_id' => $b->id,
                'goles_local' => 2, 'goles_visitante' => 0,
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect();

        $partido->refresh();
        $this->assertEquals($b->id, $partido->equipo_ausente_id);
        $this->assertEquals('2 - 0', $partido->marcador);
        DB::rollBack();
    }

    public function test_sin_marcar_walkover_no_se_exige_nada_de_eso(): void
    {
        DB::beginTransaction();
        [$t, $a, $b] = $this->escenario();
        $partido = $this->partido($t, $a, $b);

        $this->actingAs(User::firstOrFail())
            ->post(route('partidos.registro.guardar', $partido), ['confirmado' => 1])
            ->assertSessionHasNoErrors();

        $this->assertFalse($partido->fresh()->es_walkover);
        DB::rollBack();
    }

    // ---------- Las vistas ----------

    public function test_la_pantalla_de_registro_ofrece_el_walkover(): void
    {
        DB::beginTransaction();
        [$t, $a, $b] = $this->escenario();
        $partido = $this->partido($t, $a, $b);

        $html = $this->actingAs(User::firstOrFail())
            ->get(route('partidos.registro', $partido))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('name="walkover"', $html);
        $this->assertStringContainsString('name="equipo_ausente_id"', $html);
        $this->assertStringContainsString('name="goles_local"', $html);
        $this->assertStringContainsString($a->nombre, $html);
        $this->assertStringContainsString($b->nombre, $html);
        DB::rollBack();
    }

    public function test_la_portada_marca_el_resultado_como_walkover(): void
    {
        DB::beginTransaction();
        [$t, $a, $b] = $this->escenario();
        app(RegistroPartidoService::class)->registrarWalkover($this->partido($t, $a, $b), $b->id, 3, 0);

        $html = $this->get(route('home', ['torneo_id' => $t->id]))->assertOk()->getContent();

        $this->assertStringContainsString('>W.O.<', $html, 'El publico debe entender por que es 3-0.');
        $this->assertStringContainsString('no se presento', $html);
        DB::rollBack();
    }

    public function test_un_partido_normal_no_lleva_la_marca(): void
    {
        DB::beginTransaction();
        [$t, $a, $b] = $this->escenario();
        app(RegistroPartidoService::class)->registrar($this->partido($t, $a, $b), []);

        $html = $this->get(route('home', ['torneo_id' => $t->id]))->assertOk()->getContent();

        $this->assertStringNotContainsString('>W.O.<', $html);
        DB::rollBack();
    }
}
