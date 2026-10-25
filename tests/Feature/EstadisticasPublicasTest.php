<?php

namespace Tests\Feature;

use App\Models\Equipo;
use App\Models\IncidenciaPartido;
use App\Models\Partido;
use App\Models\Socio;
use App\Models\Torneo;
use App\Services\EstadisticasTorneoService;
use App\Services\RegistroPartidoService;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class EstadisticasPublicasTest extends TestCase
{
    private function socio(string $nombre, Equipo $equipo): Socio
    {
        $s = Socio::create([
            'nombre_completo' => $nombre, 'numero_documento' => uniqid('e'),
            'fecha_nacimiento' => '1990-01-01', 'entidad_salud' => 'X', 'celular' => '300',
            'tipo_sangre' => 'O+', 'direccion_residencia' => 'X', 'posicion_juego' => 'Medio',
            'nivel_jugador' => 2, 'estado' => 'activo',
        ]);
        $s->equipos()->attach($equipo->id);

        return $s;
    }

    /** Torneo con 2 categorias: A y B en "50", C y D en "60". */
    private function escenario(): array
    {
        $t = Torneo::create(['nombre' => 'Torneo publico '.uniqid(), 'fecha_inicio' => '2030-01-01']);
        $equipos = [];
        foreach ([['EA', '50'], ['EB', '50'], ['EC', '60'], ['ED', '60']] as [$n, $cat]) {
            $equipos[$n] = Equipo::create([
                'nombre' => $n.' '.uniqid(), 'torneo_id' => $t->id,
                'categoria' => $cat, 'estado' => 'activo',
            ]);
        }

        return [$t, $equipos];
    }

    private function partidoJugado(Torneo $t, Equipo $l, Equipo $v, int $gl, int $gv, string $fecha = '2030-02-01 10:00'): Partido
    {
        return Partido::create([
            'torneo_id' => $t->id, 'equipo_local_id' => $l->id, 'equipo_visitante_id' => $v->id,
            'goles_local' => $gl, 'goles_visitante' => $gv,
            'fecha_hora' => $fecha, 'estado' => Partido::ESTADO_JUGADO,
        ]);
    }

    // ---------- Posiciones por categoria ----------

    public function test_arma_una_tabla_por_cada_categoria(): void
    {
        DB::beginTransaction();
        [$t, $e] = $this->escenario();
        $this->partidoJugado($t, $e['EA'], $e['EB'], 2, 0);
        $this->partidoJugado($t, $e['EC'], $e['ED'], 1, 0);

        $porCategoria = app(EstadisticasTorneoService::class)->posicionesPorCategoria($t);

        $this->assertEqualsCanonicalizing(['Categoria 50', 'Categoria 60'], $porCategoria->keys()->all());
        $this->assertCount(2, $porCategoria['Categoria 50']);
        $this->assertCount(2, $porCategoria['Categoria 60']);

        // Cada categoria se numera desde 1
        $this->assertEquals([1, 2], $porCategoria['Categoria 50']->pluck('posicion')->all());
        $this->assertEquals([1, 2], $porCategoria['Categoria 60']->pluck('posicion')->all());
        $this->assertEquals($e['EA']->nombre, $porCategoria['Categoria 50'][0]['equipo']);

        DB::rollBack();
    }

    public function test_los_equipos_sin_categoria_no_desaparecen(): void
    {
        DB::beginTransaction();
        [$t, $e] = $this->escenario();
        Equipo::create(['nombre' => 'EE '.uniqid(), 'torneo_id' => $t->id, 'estado' => 'activo']);

        $porCategoria = app(EstadisticasTorneoService::class)->posicionesPorCategoria($t);

        $this->assertArrayHasKey('Sin categoria', $porCategoria->all());
        $this->assertCount(1, $porCategoria['Sin categoria']);
        DB::rollBack();
    }

    public function test_una_categoria_no_mezcla_equipos_de_otra(): void
    {
        DB::beginTransaction();
        [$t, $e] = $this->escenario();
        $porCategoria = app(EstadisticasTorneoService::class)->posicionesPorCategoria($t);

        $nombres50 = $porCategoria['Categoria 50']->pluck('equipo')->all();
        $this->assertContains($e['EA']->nombre, $nombres50);
        $this->assertNotContains($e['EC']->nombre, $nombres50, 'Un equipo de la 60 no debe salir en la 50.');
        DB::rollBack();
    }

    // ---------- Goleadores ----------

    public function test_cuenta_los_goles_y_no_los_autogoles(): void
    {
        DB::beginTransaction();
        [$t, $e] = $this->escenario();
        $goleador = $this->socio('P GOLEADOR', $e['EA']);
        $torpe = $this->socio('P AUTOGOL', $e['EA']);
        $partido = Partido::create([
            'torneo_id' => $t->id, 'equipo_local_id' => $e['EA']->id, 'equipo_visitante_id' => $e['EB']->id,
            'fecha_hora' => '2030-02-01 10:00', 'estado' => Partido::ESTADO_PROGRAMADO,
        ]);

        app(RegistroPartidoService::class)->registrar($partido, [
            ['socio_id' => $goleador->id, 'equipo_id' => $e['EA']->id, 'tipo' => IncidenciaPartido::TIPO_GOL],
            ['socio_id' => $goleador->id, 'equipo_id' => $e['EA']->id, 'tipo' => IncidenciaPartido::TIPO_GOL],
            ['socio_id' => $torpe->id, 'equipo_id' => $e['EA']->id, 'tipo' => IncidenciaPartido::TIPO_AUTOGOL],
        ]);

        $goleadores = app(EstadisticasTorneoService::class)->goleadoresPorCategoria($t)['Categoria 50'];

        $this->assertCount(1, $goleadores, 'El autogol no convierte en goleador.');
        $this->assertEquals('P GOLEADOR', $goleadores[0]->jugador);
        $this->assertEquals(2, $goleadores[0]->goles);
        DB::rollBack();
    }

    public function test_los_goleadores_salen_ordenados(): void
    {
        DB::beginTransaction();
        [$t, $e] = $this->escenario();
        $uno = $this->socio('P UNO', $e['EA']);
        $tres = $this->socio('P TRES', $e['EA']);
        $partido = Partido::create([
            'torneo_id' => $t->id, 'equipo_local_id' => $e['EA']->id, 'equipo_visitante_id' => $e['EB']->id,
            'fecha_hora' => '2030-02-01 10:00', 'estado' => Partido::ESTADO_PROGRAMADO,
        ]);

        $incidencias = [['socio_id' => $uno->id, 'equipo_id' => $e['EA']->id, 'tipo' => IncidenciaPartido::TIPO_GOL]];
        for ($i = 0; $i < 3; $i++) {
            $incidencias[] = ['socio_id' => $tres->id, 'equipo_id' => $e['EA']->id, 'tipo' => IncidenciaPartido::TIPO_GOL];
        }
        app(RegistroPartidoService::class)->registrar($partido, $incidencias);

        $goleadores = app(EstadisticasTorneoService::class)->goleadoresPorCategoria($t)['Categoria 50'];
        $this->assertEquals('P TRES', $goleadores[0]->jugador);
        $this->assertEquals(3, $goleadores[0]->goles);
        DB::rollBack();
    }

    public function test_los_goleadores_se_separan_por_categoria(): void
    {
        DB::beginTransaction();
        [$t, $e] = $this->escenario();
        $dela50 = $this->socio('P DE LA 50', $e['EA']);
        $dela60 = $this->socio('P DE LA 60', $e['EC']);

        foreach ([[$dela50, $e['EA'], $e['EB']], [$dela60, $e['EC'], $e['ED']]] as [$socio, $local, $visita]) {
            $partido = Partido::create([
                'torneo_id' => $t->id, 'equipo_local_id' => $local->id, 'equipo_visitante_id' => $visita->id,
                'fecha_hora' => '2030-02-01 10:00', 'estado' => Partido::ESTADO_PROGRAMADO,
            ]);
            app(RegistroPartidoService::class)->registrar($partido, [
                ['socio_id' => $socio->id, 'equipo_id' => $local->id, 'tipo' => IncidenciaPartido::TIPO_GOL],
            ]);
        }

        $porCategoria = app(EstadisticasTorneoService::class)->goleadoresPorCategoria($t);

        $this->assertEqualsCanonicalizing(['Categoria 50', 'Categoria 60'], $porCategoria->keys()->all());
        $this->assertEquals(['P DE LA 50'], $porCategoria['Categoria 50']->pluck('jugador')->all());
        $this->assertEquals(['P DE LA 60'], $porCategoria['Categoria 60']->pluck('jugador')->all());
        DB::rollBack();
    }

    // ---------- Sancionados ----------

    public function test_las_tarjetas_listan_amarillas_y_rojas_con_los_expulsados_primero(): void
    {
        DB::beginTransaction();
        [$t, $e] = $this->escenario();
        $expulsado = $this->socio('P EXPULSADO', $e['EA']);
        $amonestado = $this->socio('P AMONESTADO', $e['EA']);
        $limpio = $this->socio('P LIMPIO', $e['EA']);
        $partido = Partido::create([
            'torneo_id' => $t->id, 'equipo_local_id' => $e['EA']->id, 'equipo_visitante_id' => $e['EB']->id,
            'fecha_hora' => '2030-02-01 10:00', 'estado' => Partido::ESTADO_PROGRAMADO,
        ]);

        app(RegistroPartidoService::class)->registrar($partido, [
            ['socio_id' => $amonestado->id, 'equipo_id' => $e['EA']->id, 'tipo' => IncidenciaPartido::TIPO_AMARILLA],
            ['socio_id' => $amonestado->id, 'equipo_id' => $e['EA']->id, 'tipo' => IncidenciaPartido::TIPO_AMARILLA],
            ['socio_id' => $expulsado->id, 'equipo_id' => $e['EA']->id, 'tipo' => IncidenciaPartido::TIPO_ROJA],
        ]);

        $sancionados = app(EstadisticasTorneoService::class)->tarjetas($t);

        $this->assertCount(2, $sancionados, 'Quien no tiene tarjetas no aparece.');
        $this->assertEquals('P EXPULSADO', $sancionados[0]->jugador, 'La roja va primero.');
        $this->assertEquals(1, $sancionados[0]->rojas);
        $this->assertEquals(2, $sancionados[1]->amarillas);
        $this->assertNotContains('P LIMPIO', $sancionados->pluck('jugador')->all());
        DB::rollBack();
    }

    // ---------- Valla menos vencida ----------

    public function test_ordena_por_menos_goles_recibidos_y_excluye_a_quien_no_jugo(): void
    {
        DB::beginTransaction();
        [$t, $e] = $this->escenario();
        $this->partidoJugado($t, $e['EA'], $e['EB'], 3, 0);   // EA recibe 0, EB recibe 3
        $this->partidoJugado($t, $e['EC'], $e['ED'], 1, 1);   // EC y ED reciben 1

        $valla = app(EstadisticasTorneoService::class)->vallaPorCategoria($t);

        $this->assertEqualsCanonicalizing(['Categoria 50', 'Categoria 60'], $valla->keys()->all());
        $this->assertEquals($e['EA']->nombre, $valla['Categoria 50'][0]['equipo'], 'El que menos recibio va primero.');
        $this->assertEquals(0, $valla['Categoria 50'][0]['gc']);
        $this->assertCount(2, $valla['Categoria 50'], 'Solo los dos equipos de la 50.');
        $this->assertCount(2, $valla['Categoria 60']);

        // Un equipo que no jugo no entra
        Equipo::create(['nombre' => 'SIN JUGAR '.uniqid(), 'torneo_id' => $t->id, 'categoria' => '50', 'estado' => 'activo']);
        $valla2 = app(EstadisticasTorneoService::class)->vallaPorCategoria($t);
        $this->assertCount(2, $valla2['Categoria 50'], 'Un equipo sin partidos no cuenta para la valla.');
        DB::rollBack();
    }

    public function test_la_valla_recorta_a_los_mejores_de_cada_categoria(): void
    {
        DB::beginTransaction();
        [$t, $e] = $this->escenario();
        $this->partidoJugado($t, $e['EA'], $e['EB'], 1, 0);
        $this->partidoJugado($t, $e['EC'], $e['ED'], 1, 0);

        $valla = app(EstadisticasTorneoService::class)->vallaPorCategoria($t, 1);

        $this->assertCount(1, $valla['Categoria 50'], 'El limite se aplica dentro de cada categoria.');
        $this->assertCount(1, $valla['Categoria 60']);
        $this->assertEquals($e['EA']->nombre, $valla['Categoria 50'][0]['equipo']);
        DB::rollBack();
    }

    // ---------- Resultados ----------

    public function test_muestra_solo_partidos_jugados_del_mas_reciente(): void
    {
        DB::beginTransaction();
        [$t, $e] = $this->escenario();
        $this->partidoJugado($t, $e['EA'], $e['EB'], 1, 0, '2030-02-01 10:00');
        $this->partidoJugado($t, $e['EC'], $e['ED'], 2, 2, '2030-03-01 10:00');
        Partido::create([
            'torneo_id' => $t->id, 'equipo_local_id' => $e['EA']->id, 'equipo_visitante_id' => $e['EC']->id,
            'fecha_hora' => '2030-04-01 10:00', 'estado' => Partido::ESTADO_PROGRAMADO,
        ]);

        $porCategoria = app(EstadisticasTorneoService::class)->resultadosPorCategoria($t);

        $this->assertCount(2, $porCategoria->flatten(), 'El programado no es un resultado.');
        $this->assertEqualsCanonicalizing(['Categoria 50', 'Categoria 60'], $porCategoria->keys()->all());

        // Dentro de cada categoria, el mas reciente va primero.
        $this->partidoJugado($t, $e['EA'], $e['EB'], 0, 0, '2030-05-01 10:00');
        $deLa50 = app(EstadisticasTorneoService::class)->resultadosPorCategoria($t)['Categoria 50'];
        $this->assertEquals('2030-05-01', $deLa50->first()->fecha_hora->toDateString());
        DB::rollBack();
    }

    public function test_un_torneo_no_mezcla_datos_de_otro(): void
    {
        DB::beginTransaction();
        [$t1, $e1] = $this->escenario();
        [$t2, $e2] = $this->escenario();
        $this->partidoJugado($t1, $e1['EA'], $e1['EB'], 5, 0);
        $this->partidoJugado($t2, $e2['EA'], $e2['EB'], 1, 0);

        $servicio = app(EstadisticasTorneoService::class);
        $this->assertCount(1, $servicio->resultadosPorCategoria($t1)->flatten());
        $this->assertEquals(5, $servicio->resultadosPorCategoria($t1)->flatten()->first()->goles_local);
        DB::rollBack();
    }

    // ---------- La pagina ----------

    public function test_la_pagina_es_publica_y_muestra_las_cinco_secciones(): void
    {
        DB::beginTransaction();
        [$t, $e] = $this->escenario();
        $goleador = $this->socio('P PUBLICO', $e['EA']);
        $partido = Partido::create([
            'torneo_id' => $t->id, 'equipo_local_id' => $e['EA']->id, 'equipo_visitante_id' => $e['EB']->id,
            'fecha_hora' => '2030-02-01 10:00', 'estado' => Partido::ESTADO_PROGRAMADO,
        ]);
        app(RegistroPartidoService::class)->registrar($partido, [
            ['socio_id' => $goleador->id, 'equipo_id' => $e['EA']->id, 'tipo' => IncidenciaPartido::TIPO_GOL],
            ['socio_id' => $goleador->id, 'equipo_id' => $e['EA']->id, 'tipo' => IncidenciaPartido::TIPO_ROJA],
        ]);

        // Sin iniciar sesion
        $respuesta = $this->get(route('home', ['torneo_id' => $t->id]));

        $respuesta->assertOk()
            ->assertSee('Tabla de posiciones', false)
            ->assertSee('Goleadores', false)
            ->assertSee('Valla menos vencida', false)
            ->assertSee('Sancionados', false)
            ->assertSee('Tarjetas', false)
            ->assertSee('Resultados', false)
            ->assertSee('Categoria 50', false)
            ->assertSee('P PUBLICO', false);

        DB::rollBack();
    }

    /**
     * El estado vacio se comprueba renderizando la vista sin torneo, en vez
     * de borrar los torneos de la base (eso bloquearia filas referenciadas).
     */
    public function test_la_pagina_responde_sin_torneos(): void
    {
        $html = view('home', [
            'torneos' => collect(),
            'torneo' => null,
            'posicionesPorCategoria' => collect(),
            'goleadoresPorCategoria' => collect(),
            'sancionados' => collect(),
            'tarjetas' => collect(),
            'vallaPorCategoria' => collect(),
            'resultadosPorCategoria' => collect(),
            'nombreSistema' => 'Sistema',
            'logoUrl' => null,
            'descripcionPortada' => 'Descripcion',
        ])->render();

        $this->assertStringContainsString('Todavia no hay torneos publicados', $html);
    }

    public function test_un_torneo_sin_partidos_muestra_los_mensajes_vacios(): void
    {
        DB::beginTransaction();
        [$t] = $this->escenario();

        $this->get(route('home', ['torneo_id' => $t->id]))
            ->assertOk()
            ->assertSee('Aun no se han registrado goles', false)
            ->assertSee('Aun no hay partidos jugados', false)
            ->assertSee('No hay jugadores con tarjetas', false);

        DB::rollBack();
    }

    public function test_la_portada_muestra_las_estadisticas_y_el_menu(): void
    {
        $respuesta = $this->get(route('home'));

        $respuesta->assertOk()
            ->assertSee('Tabla de posiciones', false)
            ->assertSee('nav-publico', false)
            ->assertSee(route('consulta.index'), false)
            ->assertSee(route('noticias-publicas.index'), false);
    }

    public function test_el_enlace_viejo_de_estadisticas_lleva_a_la_portada(): void
    {
        $this->get('/estadisticas')->assertRedirect('/');
    }

    public function test_el_menu_publico_aparece_en_todas_las_paginas_publicas(): void
    {
        foreach ([route('home'), route('consulta.index'), route('noticias-publicas.index')] as $url) {
            $this->get($url)->assertOk()->assertSee('nav-publico', false);
        }
    }

    public function test_la_pagina_publica_no_expone_datos_financieros(): void
    {
        DB::beginTransaction();
        [$t, $e] = $this->escenario();
        $socio = $this->socio('P CON DEUDA', $e['EA']);
        $socio->cargos()->create([
            'tipo_cargo_id' => \App\Models\TipoCargo::firstOrFail()->id,
            'monto' => 123456, 'fecha' => '2030-01-01',
        ]);

        $html = $this->get(route('home', ['torneo_id' => $t->id]))->getContent();

        $this->assertStringNotContainsString('123.456', $html, 'La pagina publica no debe mostrar deudas.');
        $this->assertStringNotContainsString('Deuda', $html);
        $this->assertStringNotContainsString($socio->numero_documento, $html, 'No debe exponer documentos.');
        DB::rollBack();
    }
}
