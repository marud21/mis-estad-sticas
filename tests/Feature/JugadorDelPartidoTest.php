<?php

namespace Tests\Feature;

use App\Models\Equipo;
use App\Models\IncidenciaPartido;
use App\Models\Partido;
use App\Models\Socio;
use App\Models\Torneo;
use App\Models\User;
use App\Services\EstadisticasTorneoService;
use App\Services\RegistroPartidoService;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class JugadorDelPartidoTest extends TestCase
{
    private function socio(string $nombre, ?Equipo $equipo = null): Socio
    {
        $s = Socio::create([
            'nombre_completo' => $nombre, 'numero_documento' => uniqid('f'),
            'fecha_nacimiento' => '1990-01-01', 'entidad_salud' => 'X', 'celular' => '300',
            'tipo_sangre' => 'O+', 'direccion_residencia' => 'X', 'posicion_juego' => 'Medio',
            'nivel_jugador' => 2, 'estado' => 'activo',
        ]);

        if ($equipo) {
            $s->equipos()->attach($equipo->id);
        }

        return $s;
    }

    /** @return array{0:Torneo,1:Equipo,2:Equipo} */
    private function escenario(): array
    {
        $t = Torneo::create(['nombre' => 'Torneo figura '.uniqid(), 'fecha_inicio' => '2030-01-01']);
        $a = Equipo::create(['nombre' => 'FA '.uniqid(), 'torneo_id' => $t->id, 'categoria' => '50', 'estado' => 'activo']);
        $b = Equipo::create(['nombre' => 'FB '.uniqid(), 'torneo_id' => $t->id, 'categoria' => '50', 'estado' => 'activo']);

        return [$t, $a, $b];
    }

    private function partido(Torneo $t, Equipo $l, Equipo $v, string $fecha, ?string $fase = 'Jornada 1'): Partido
    {
        return Partido::create([
            'torneo_id' => $t->id, 'equipo_local_id' => $l->id, 'equipo_visitante_id' => $v->id,
            'fecha_hora' => $fecha, 'estado' => Partido::ESTADO_PROGRAMADO, 'fase' => $fase,
        ]);
    }

    private function servicio(): EstadisticasTorneoService
    {
        return app(EstadisticasTorneoService::class);
    }

    // ---------- Guardar la figura ----------

    public function test_guarda_la_figura_al_registrar_el_resultado(): void
    {
        DB::beginTransaction();
        [$t, $a, $b] = $this->escenario();
        $figura = $this->socio('F ELEGIDO', $a);
        $partido = $this->partido($t, $a, $b, '2030-02-01 10:00');

        app(RegistroPartidoService::class)->registrar($partido, [
            ['socio_id' => $figura->id, 'equipo_id' => $a->id, 'tipo' => IncidenciaPartido::TIPO_GOL],
        ], $figura->id);

        $this->assertEquals($figura->id, $partido->fresh()->jugador_partido_id);
        DB::rollBack();
    }

    public function test_la_figura_es_opcional(): void
    {
        DB::beginTransaction();
        [$t, $a, $b] = $this->escenario();
        $jugador = $this->socio('F SIN ELEGIR', $a);
        $partido = $this->partido($t, $a, $b, '2030-02-01 10:00');

        app(RegistroPartidoService::class)->registrar($partido, [
            ['socio_id' => $jugador->id, 'equipo_id' => $a->id, 'tipo' => IncidenciaPartido::TIPO_GOL],
        ]);

        $partido->refresh();
        $this->assertNull($partido->jugador_partido_id);
        $this->assertEquals(Partido::ESTADO_JUGADO, $partido->estado, 'El resultado se guarda igual.');
        DB::rollBack();
    }

    public function test_registrar_de_nuevo_sin_figura_la_quita(): void
    {
        DB::beginTransaction();
        [$t, $a, $b] = $this->escenario();
        $figura = $this->socio('F QUE SE QUITA', $a);
        $partido = $this->partido($t, $a, $b, '2030-02-01 10:00');
        $servicio = app(RegistroPartidoService::class);

        $servicio->registrar($partido, [], $figura->id);
        $this->assertEquals($figura->id, $partido->fresh()->jugador_partido_id);

        $servicio->registrar($partido->fresh(), []);
        $this->assertNull($partido->fresh()->jugador_partido_id, 'Manda lo ultimo que se guardo.');
        DB::rollBack();
    }

    public function test_rechaza_una_figura_que_no_jugo_el_partido(): void
    {
        DB::beginTransaction();
        [$t, $a, $b] = $this->escenario();
        $otroEquipo = Equipo::create(['nombre' => 'FC '.uniqid(), 'torneo_id' => $t->id, 'estado' => 'activo']);
        $ajeno = $this->socio('F AJENO', $otroEquipo);
        $partido = $this->partido($t, $a, $b, '2030-02-01 10:00');

        $this->actingAs(User::firstOrFail())
            ->post(route('partidos.registro.guardar', $partido), [
                'jugador_partido_id' => $ajeno->id,
                'confirmado' => 1,
            ])
            ->assertSessionHasErrors('jugador_partido_id');

        $this->assertNull($partido->fresh()->jugador_partido_id);
        DB::rollBack();
    }

    public function test_acepta_una_figura_de_cualquiera_de_los_dos_equipos(): void
    {
        DB::beginTransaction();
        [$t, $a, $b] = $this->escenario();
        $delVisitante = $this->socio('F DEL VISITANTE', $b);
        $partido = $this->partido($t, $a, $b, '2030-02-01 10:00');

        $this->actingAs(User::firstOrFail())
            ->post(route('partidos.registro.guardar', $partido), [
                'jugador_partido_id' => $delVisitante->id,
                'confirmado' => 1,
            ])
            ->assertSessionHasNoErrors();

        $this->assertEquals($delVisitante->id, $partido->fresh()->jugador_partido_id);
        DB::rollBack();
    }

    public function test_la_pantalla_de_registro_ofrece_elegir_la_figura(): void
    {
        DB::beginTransaction();
        [$t, $a, $b] = $this->escenario();
        $local = $this->socio('F LOCAL', $a);
        $visitante = $this->socio('F VISITA', $b);
        $partido = $this->partido($t, $a, $b, '2030-02-01 10:00');

        $html = $this->actingAs(User::firstOrFail())
            ->get(route('partidos.registro', $partido))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('Jugador del partido', $html);
        $this->assertStringContainsString('name="jugador_partido_id"', $html);
        $this->assertStringContainsString('-- Sin eleccion --', $html, 'Debe poder dejarse vacio.');
        $this->assertStringContainsString('F LOCAL', $html);
        $this->assertStringContainsString('F VISITA', $html, 'Los dos equipos en la misma lista.');
        DB::rollBack();
    }

    // ---------- La vista publica ----------

    public function test_muestra_las_figuras_de_la_ultima_jornada(): void
    {
        DB::beginTransaction();
        [$t, $a, $b] = $this->escenario();
        $viejo = $this->socio('F DE LA JORNADA VIEJA', $a);
        $nuevo = $this->socio('F DE LA JORNADA NUEVA', $a);
        $servicio = app(RegistroPartidoService::class);

        $servicio->registrar($this->partido($t, $a, $b, '2030-02-01 10:00', 'Jornada 1'), [], $viejo->id);
        $servicio->registrar($this->partido($t, $a, $b, '2030-03-01 10:00', 'Jornada 2'), [], $nuevo->id);

        $resultado = $this->servicio()->mejoresPorCategoria($t);

        $this->assertEquals('Jornada 2', $resultado['Categoria 50']['jornada']);
        $this->assertEquals(['F DE LA JORNADA NUEVA'], $resultado['Categoria 50']['figuras']->pluck('jugador')->all());
        DB::rollBack();
    }

    public function test_la_jornada_se_elige_por_fecha_y_no_por_el_nombre(): void
    {
        DB::beginTransaction();
        [$t, $a, $b] = $this->escenario();
        $dos = $this->socio('F JORNADA 2', $a);
        $diez = $this->socio('F JORNADA 10', $a);
        $servicio = app(RegistroPartidoService::class);

        // "Jornada 10" va despues de la 2 en el calendario, pero antes si
        // se ordenara por texto.
        $servicio->registrar($this->partido($t, $a, $b, '2030-02-01 10:00', 'Jornada 2'), [], $dos->id);
        $servicio->registrar($this->partido($t, $a, $b, '2030-06-01 10:00', 'Jornada 10'), [], $diez->id);

        $resultado = $this->servicio()->mejoresPorCategoria($t);

        $this->assertEquals('Jornada 10', $resultado['Categoria 50']['jornada'], 'Manda la fecha, no el orden alfabetico.');
        DB::rollBack();
    }

    public function test_junta_todas_las_figuras_de_esa_jornada(): void
    {
        DB::beginTransaction();
        [$t, $a, $b] = $this->escenario();
        $uno = $this->socio('F UNO', $a);
        $dos = $this->socio('F DOS', $b);
        $servicio = app(RegistroPartidoService::class);

        $servicio->registrar($this->partido($t, $a, $b, '2030-03-01 10:00', 'Jornada 5'), [], $uno->id);
        $servicio->registrar($this->partido($t, $b, $a, '2030-03-02 10:00', 'Jornada 5'), [], $dos->id);
        // Un tercer partido de la misma jornada, sin figura elegida.
        $servicio->registrar($this->partido($t, $a, $b, '2030-03-03 10:00', 'Jornada 5'), []);

        $resultado = $this->servicio()->mejoresPorCategoria($t);

        $this->assertEquals('Jornada 5', $resultado['Categoria 50']['jornada']);
        $this->assertEqualsCanonicalizing(['F UNO', 'F DOS'], $resultado['Categoria 50']['figuras']->pluck('jugador')->all());
        DB::rollBack();
    }

    public function test_muestra_el_equipo_con_el_que_jugo_la_figura(): void
    {
        DB::beginTransaction();
        [$t, $a, $b] = $this->escenario();
        $otroEquipo = Equipo::create(['nombre' => 'FD '.uniqid(), 'torneo_id' => $t->id, 'estado' => 'activo']);
        $figura = $this->socio('F DE DOS EQUIPOS', $otroEquipo);
        $figura->equipos()->attach($b->id);

        app(RegistroPartidoService::class)->registrar($this->partido($t, $a, $b, '2030-03-01 10:00'), [], $figura->id);

        $datos = $this->servicio()->mejoresPorCategoria($t)['Categoria 50']['figuras']->first();

        $this->assertEquals($b->nombre, $datos->equipo, 'El equipo con el que jugo, no el primero de su lista.');
        DB::rollBack();
    }

    public function test_sin_figuras_no_hay_nada_que_mostrar(): void
    {
        DB::beginTransaction();
        [$t, $a, $b] = $this->escenario();
        app(RegistroPartidoService::class)->registrar($this->partido($t, $a, $b, '2030-03-01 10:00'), []);

        $resultado = $this->servicio()->mejoresPorCategoria($t);

        $this->assertTrue($resultado->isEmpty(), 'Sin figuras, la categoria no aparece.');

        // Se mira el marcado, no el texto suelto: la frase tambien aparece
        // en un comentario de la hoja de estilos.
        $html = $this->get(route('home', ['torneo_id' => $t->id]))->assertOk()->getContent();
        $this->assertStringNotContainsString('<ul class="figuras">', $html);
        DB::rollBack();
    }

    public function test_un_partido_sin_jugar_no_aporta_figura(): void
    {
        DB::beginTransaction();
        [$t, $a, $b] = $this->escenario();
        $figura = $this->socio('F DE PARTIDO PENDIENTE', $a);
        $jugada = $this->socio('F DE PARTIDO JUGADO', $a);

        app(RegistroPartidoService::class)->registrar($this->partido($t, $a, $b, '2030-03-01 10:00', 'Jornada 3'), [], $jugada->id);

        // Programado para despues, con figura puesta a mano: no cuenta.
        $this->partido($t, $a, $b, '2030-04-01 10:00', 'Jornada 4')->update(['jugador_partido_id' => $figura->id]);

        $resultado = $this->servicio()->mejoresPorCategoria($t);

        $this->assertEquals('Jornada 3', $resultado['Categoria 50']['jornada'], 'La ultima jornada es la ultima JUGADA.');
        $this->assertEquals(['F DE PARTIDO JUGADO'], $resultado['Categoria 50']['figuras']->pluck('jugador')->all());
        DB::rollBack();
    }

    public function test_cada_categoria_trae_su_propia_jornada(): void
    {
        DB::beginTransaction();
        [$t, $a, $b] = $this->escenario();
        // Dos equipos mas, de otra categoria, en el mismo torneo.
        $c = Equipo::create(['nombre' => 'FC '.uniqid(), 'torneo_id' => $t->id, 'categoria' => '60', 'estado' => 'activo']);
        $d = Equipo::create(['nombre' => 'FD '.uniqid(), 'torneo_id' => $t->id, 'categoria' => '60', 'estado' => 'activo']);

        $deLa50 = $this->socio('F DE LA 50', $a);
        $deLa60 = $this->socio('F DE LA 60', $c);
        $servicio = app(RegistroPartidoService::class);

        // La 50 va mas adelantada: juega la 15 en junio.
        $servicio->registrar($this->partido($t, $a, $b, '2030-06-01 10:00', 'Jornada 15'), [], $deLa50->id);
        // La 60 va atrasada: su ultima es la 12, en marzo.
        $servicio->registrar($this->partido($t, $c, $d, '2030-03-01 10:00', 'Jornada 12'), [], $deLa60->id);

        $porCategoria = $this->servicio()->mejoresPorCategoria($t);

        $this->assertEqualsCanonicalizing(['Categoria 50', 'Categoria 60'], $porCategoria->keys()->all());
        $this->assertEquals('Jornada 15', $porCategoria['Categoria 50']['jornada']);
        $this->assertEquals('Jornada 12', $porCategoria['Categoria 60']['jornada'], 'La categoria atrasada no debe quedar tapada.');
        $this->assertEquals(['F DE LA 50'], $porCategoria['Categoria 50']['figuras']->pluck('jugador')->all());
        $this->assertEquals(['F DE LA 60'], $porCategoria['Categoria 60']['figuras']->pluck('jugador')->all());
        DB::rollBack();
    }

    public function test_una_categoria_sin_figuras_no_aparece(): void
    {
        DB::beginTransaction();
        [$t, $a, $b] = $this->escenario();
        $c = Equipo::create(['nombre' => 'FE '.uniqid(), 'torneo_id' => $t->id, 'categoria' => '60', 'estado' => 'activo']);
        $d = Equipo::create(['nombre' => 'FF '.uniqid(), 'torneo_id' => $t->id, 'categoria' => '60', 'estado' => 'activo']);

        $figura = $this->socio('F UNICA', $a);
        $servicio = app(RegistroPartidoService::class);

        $servicio->registrar($this->partido($t, $a, $b, '2030-03-01 10:00'), [], $figura->id);
        // La 60 jugo, pero nadie eligio figura.
        $servicio->registrar($this->partido($t, $c, $d, '2030-03-02 10:00'), []);

        $porCategoria = $this->servicio()->mejoresPorCategoria($t);

        $this->assertEquals(['Categoria 50'], $porCategoria->keys()->all());
        DB::rollBack();
    }

    public function test_la_portada_lista_a_las_figuras(): void
    {
        DB::beginTransaction();
        [$t, $a, $b] = $this->escenario();
        $figura = $this->socio('FIGURA EN PORTADA', $a);
        app(RegistroPartidoService::class)->registrar($this->partido($t, $a, $b, '2030-03-01 10:00', 'Jornada 7'), [], $figura->id);

        $html = $this->get(route('home', ['torneo_id' => $t->id]))->assertOk()->getContent();

        $this->assertStringContainsString('<ul class="figuras">', $html, 'La seccion debe dibujarse de verdad.');
        $this->assertStringContainsString('<h2>&#11088; Mejores de la jornada</h2>', $html);
        $this->assertStringContainsString('<h3>Categoria 50</h3>', $html, 'Separado por categoria, como el resto.');
        $this->assertStringContainsString('Jornada 7', $html);
        $this->assertStringContainsString('FIGURA EN PORTADA', $html);
        $this->assertStringContainsString($a->nombre, $html, 'Debe verse el equipo.');
        DB::rollBack();
    }
}
