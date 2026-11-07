<?php

namespace Tests\Feature;

use App\Models\Cargo;
use App\Models\Equipo;
use App\Models\IncidenciaPartido;
use App\Models\Pago;
use App\Models\Partido;
use App\Models\Socio;
use App\Models\TipoCargo;
use App\Models\Torneo;
use App\Models\User;
use App\Services\PanelService;
use App\Services\RegistroPartidoService;
use App\Support\FiltroPanel;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PanelTest extends TestCase
{
    private function filtro(array $parametros = []): FiltroPanel
    {
        return FiltroPanel::desdeLaPeticion(Request::create('/panel', 'GET', $parametros));
    }

    private function panel(): PanelService
    {
        return app(PanelService::class);
    }

    /** @return array{0:Torneo,1:Equipo,2:Equipo} */
    private function escenario(): array
    {
        $t = Torneo::create(['nombre' => 'Torneo panel '.uniqid(), 'fecha_inicio' => '2030-01-01']);
        $a = Equipo::create(['nombre' => 'PA '.uniqid(), 'torneo_id' => $t->id, 'categoria' => '50', 'estado' => 'activo']);
        $b = Equipo::create(['nombre' => 'PB '.uniqid(), 'torneo_id' => $t->id, 'categoria' => '60', 'estado' => 'activo']);

        return [$t, $a, $b];
    }

    private function socio(string $nombre, ?Equipo $equipo = null, string $estado = Socio::ESTADO_ACTIVO): Socio
    {
        $s = Socio::create([
            'nombre_completo' => $nombre, 'numero_documento' => uniqid('pn'),
            'fecha_nacimiento' => '1990-01-01', 'entidad_salud' => 'X', 'celular' => '300',
            'tipo_sangre' => 'O+', 'direccion_residencia' => 'X', 'posicion_juego' => 'Medio',
            'nivel_jugador' => 2, 'estado' => $estado,
        ]);

        if ($equipo) {
            $s->equipos()->attach($equipo->id);
        }

        return $s;
    }

    private function tipoCargo(): TipoCargo
    {
        return TipoCargo::firstOrCreate(
            ['nombre' => 'Mensualidad'],
            ['monto_default' => 10000, 'es_recurrente' => true, 'porcentaje_suspendido' => 50],
        );
    }

    // ---------- El filtro ----------

    public function test_por_defecto_el_rango_es_el_año_en_curso(): void
    {
        $f = $this->filtro();

        $this->assertEquals(CarbonImmutable::today()->startOfYear()->toDateString(), $f->desde->toDateString());
        $this->assertEquals(CarbonImmutable::today()->endOfYear()->toDateString(), $f->hasta->toDateString());
    }

    public function test_endereza_un_rango_al_reves(): void
    {
        $f = $this->filtro(['desde' => '2030-12-31', 'hasta' => '2030-01-01']);

        $this->assertEquals('2030-01-01', $f->desde->toDateString(), 'Se intercambian en vez de no devolver nada.');
        $this->assertEquals('2030-12-31', $f->hasta->toDateString());
    }

    public function test_una_fecha_invalida_no_rompe_el_panel(): void
    {
        $f = $this->filtro(['desde' => 'no-es-fecha']);

        $this->assertEquals(CarbonImmutable::today()->startOfYear()->toDateString(), $f->desde->toDateString());
    }

    public function test_sin_filtros_de_equipo_alcanza_a_todos(): void
    {
        $this->assertNull($this->filtro()->equiposAlcanzados(), 'Null significa "todos", no "ninguno".');
    }

    public function test_el_filtro_de_categoria_acota_los_equipos(): void
    {
        DB::beginTransaction();
        [$t, $a, $b] = $this->escenario();

        $alcanzados = $this->filtro(['torneo_id' => $t->id, 'categoria' => '50'])->equiposAlcanzados();

        $this->assertEquals([$a->id], $alcanzados);
        DB::rollBack();
    }

    // ---------- Socios ----------

    public function test_cuenta_los_socios_por_estado(): void
    {
        DB::beginTransaction();
        [$t, $a] = $this->escenario();
        $this->socio('P ACTIVO UNO', $a);
        $this->socio('P ACTIVO DOS', $a);
        $this->socio('P SOSTENIMIENTO', $a, Socio::ESTADO_SUSPENDIDO);

        $datos = $this->panel()->socios($this->filtro(['equipo_id' => $a->id]));

        $this->assertEquals(3, $datos['total']);
        $porEstado = $datos['por_estado']->keyBy('valor');
        $this->assertEquals(2, $porEstado[Socio::ESTADO_ACTIVO]['total']);
        $this->assertEquals(1, $porEstado[Socio::ESTADO_SUSPENDIDO]['total']);
        $this->assertEquals(0, $porEstado[Socio::ESTADO_RETIRADO]['total']);
        DB::rollBack();
    }

    public function test_los_estados_salen_con_su_etiqueta_en_pantalla(): void
    {
        $datos = $this->panel()->socios($this->filtro());
        $etiquetas = $datos['por_estado']->pluck('etiqueta')->all();

        $this->assertContains('Sostenimiento', $etiquetas, 'Se respeta el nombre que ve el usuario.');
        $this->assertNotContains('Suspendido', $etiquetas);
    }

    // ---------- Finanzas ----------

    public function test_suma_lo_facturado_y_lo_recaudado_del_rango(): void
    {
        DB::beginTransaction();
        [$t, $a] = $this->escenario();
        $socio = $this->socio('P PAGADOR', $a);
        $tipo = $this->tipoCargo();

        // Dentro del rango
        Cargo::create(['socio_id' => $socio->id, 'equipo_id' => $a->id, 'tipo_cargo_id' => $tipo->id, 'monto' => 10000, 'fecha' => '2030-03-05']);
        Pago::create(['socio_id' => $socio->id, 'equipo_id' => $a->id, 'valor' => 4000, 'fecha' => '2030-03-10', 'tipo' => 'efectivo']);
        // Fuera del rango
        Cargo::create(['socio_id' => $socio->id, 'equipo_id' => $a->id, 'tipo_cargo_id' => $tipo->id, 'monto' => 7000, 'fecha' => '2031-03-05']);

        $f = $this->filtro(['desde' => '2030-01-01', 'hasta' => '2030-12-31', 'equipo_id' => $a->id]);
        $datos = $this->panel()->finanzas($f);

        $this->assertEquals(10000, $datos['facturado_rango'], 'Solo el cargo del rango.');
        $this->assertEquals(4000, $datos['recaudado_rango']);
        $this->assertEquals(40, $datos['cumplimiento']);
        DB::rollBack();
    }

    public function test_la_deuda_es_historica_y_no_del_rango(): void
    {
        DB::beginTransaction();
        [$t, $a] = $this->escenario();
        $socio = $this->socio('P DEUDOR', $a);
        $tipo = $this->tipoCargo();

        Cargo::create(['socio_id' => $socio->id, 'equipo_id' => $a->id, 'tipo_cargo_id' => $tipo->id, 'monto' => 10000, 'fecha' => '2030-03-05']);
        Cargo::create(['socio_id' => $socio->id, 'equipo_id' => $a->id, 'tipo_cargo_id' => $tipo->id, 'monto' => 7000, 'fecha' => '2031-03-05']);
        Pago::create(['socio_id' => $socio->id, 'equipo_id' => $a->id, 'valor' => 2000, 'fecha' => '2030-03-10', 'tipo' => 'efectivo']);

        $f = $this->filtro(['desde' => '2030-01-01', 'hasta' => '2030-12-31', 'equipo_id' => $a->id]);
        $datos = $this->panel()->finanzas($f);

        $this->assertEquals(15000, $datos['deuda_total'], 'Lo que se debe hoy incluye lo de otros años.');
        DB::rollBack();
    }

    public function test_el_grafico_trae_los_meses_vacios_del_rango(): void
    {
        DB::beginTransaction();
        [$t, $a] = $this->escenario();
        $socio = $this->socio('P DE UN MES', $a);
        Pago::create(['socio_id' => $socio->id, 'equipo_id' => $a->id, 'valor' => 5000, 'fecha' => '2030-02-10', 'tipo' => 'efectivo']);

        $f = $this->filtro(['desde' => '2030-01-01', 'hasta' => '2030-04-30', 'equipo_id' => $a->id]);
        $meses = $this->panel()->finanzas($f)['por_mes'];

        $this->assertCount(4, $meses, 'Enero a abril, aunque tres esten en cero.');
        $this->assertEquals(0.0, $meses->firstWhere('clave', '2030-01')['valor']);
        $this->assertEquals(5000.0, $meses->firstWhere('clave', '2030-02')['valor']);
        DB::rollBack();
    }

    public function test_sin_facturar_el_cumplimiento_no_se_inventa(): void
    {
        DB::beginTransaction();
        [$t, $a] = $this->escenario();

        $datos = $this->panel()->finanzas($this->filtro(['desde' => '2035-01-01', 'hasta' => '2035-12-31', 'equipo_id' => $a->id]));

        $this->assertNull($datos['cumplimiento'], 'Dividir por cero no da 0%, da nada.');
        DB::rollBack();
    }

    // ---------- Deportivo ----------

    public function test_cuenta_partidos_goles_y_tarjetas_del_rango(): void
    {
        DB::beginTransaction();
        [$t, $a, $b] = $this->escenario();
        $jugador = $this->socio('P GOLEADOR', $a);
        TipoCargo::firstOrCreate(['nombre' => 'Amarillas'], ['monto_default' => 5000, 'es_recurrente' => false, 'porcentaje_suspendido' => 100]);

        $partido = Partido::create([
            'torneo_id' => $t->id, 'equipo_local_id' => $a->id, 'equipo_visitante_id' => $b->id,
            'fecha_hora' => '2030-05-01 10:00', 'estado' => Partido::ESTADO_PROGRAMADO, 'fase' => 'Jornada 1',
        ]);

        app(RegistroPartidoService::class)->registrar($partido, [
            ['socio_id' => $jugador->id, 'equipo_id' => $a->id, 'tipo' => IncidenciaPartido::TIPO_GOL],
            ['socio_id' => $jugador->id, 'equipo_id' => $a->id, 'tipo' => IncidenciaPartido::TIPO_GOL],
            ['socio_id' => $jugador->id, 'equipo_id' => $a->id, 'tipo' => IncidenciaPartido::TIPO_AMARILLA],
        ]);

        $datos = $this->panel()->deportivo($this->filtro([
            'desde' => '2030-01-01', 'hasta' => '2030-12-31', 'torneo_id' => $t->id,
        ]));

        $this->assertEquals(1, $datos['jugados']);
        $this->assertEquals(2, $datos['goles']);
        $this->assertEquals(1, $datos['amarillas']);
        $this->assertEquals(0, $datos['rojas']);
        $this->assertEquals('P GOLEADOR', $datos['goleadores']->first()->nombre_completo);
        DB::rollBack();
    }

    public function test_un_partido_fuera_del_rango_no_cuenta(): void
    {
        DB::beginTransaction();
        [$t, $a, $b] = $this->escenario();
        $partido = Partido::create([
            'torneo_id' => $t->id, 'equipo_local_id' => $a->id, 'equipo_visitante_id' => $b->id,
            'fecha_hora' => '2031-05-01 10:00', 'estado' => Partido::ESTADO_PROGRAMADO,
        ]);
        app(RegistroPartidoService::class)->registrar($partido, []);

        $datos = $this->panel()->deportivo($this->filtro([
            'desde' => '2030-01-01', 'hasta' => '2030-12-31', 'torneo_id' => $t->id,
        ]));

        $this->assertEquals(0, $datos['jugados']);
        DB::rollBack();
    }

    public function test_lista_los_equipos_que_mas_deben(): void
    {
        DB::beginTransaction();
        [$t, $a, $b] = $this->escenario();
        $tipo = $this->tipoCargo();
        $unoA = $this->socio('P DEL A', $a);
        $unoB = $this->socio('P DEL B', $b);

        Cargo::create(['socio_id' => $unoA->id, 'equipo_id' => $a->id, 'tipo_cargo_id' => $tipo->id, 'monto' => 50000, 'fecha' => '2030-03-01']);
        Cargo::create(['socio_id' => $unoB->id, 'equipo_id' => $b->id, 'tipo_cargo_id' => $tipo->id, 'monto' => 20000, 'fecha' => '2030-03-01']);
        Pago::create(['socio_id' => $unoB->id, 'equipo_id' => $b->id, 'valor' => 20000, 'fecha' => '2030-03-02', 'tipo' => 'efectivo']);

        $datos = $this->panel()->deportivo($this->filtro(['torneo_id' => $t->id]));
        $deudores = $datos['equipos_mas_deben'];

        $this->assertCount(1, $deudores, 'El equipo que quedo al dia no aparece.');
        $this->assertEquals($a->nombre, $deudores->first()['equipo']);
        $this->assertEquals(50000.0, $deudores->first()['deuda']);
        DB::rollBack();
    }

    // ---------- La pantalla ----------

    public function test_el_panel_es_privado(): void
    {
        $this->get(route('panel.index'))->assertRedirect(route('login'));
    }

    public function test_el_panel_carga_con_los_datos_reales(): void
    {
        $html = $this->actingAs(User::firstOrFail())
            ->get(route('panel.index'))
            ->assertOk()
            ->getContent();

        foreach (['Finanzas', 'Socios', 'Deportivo', 'Deuda acumulada', 'Recaudado en el periodo'] as $texto) {
            $this->assertStringContainsString($texto, $html);
        }
    }

    public function test_el_panel_acepta_los_filtros_por_url(): void
    {
        DB::beginTransaction();
        [$t, $a] = $this->escenario();

        $this->actingAs(User::firstOrFail())
            ->get(route('panel.index', [
                'desde' => '2030-01-01', 'hasta' => '2030-06-30',
                'torneo_id' => $t->id, 'categoria' => '50', 'equipo_id' => $a->id,
            ]))
            ->assertOk()
            ->assertSee('01/01/2030 al 30/06/2030');
        DB::rollBack();
    }

    public function test_al_iniciar_sesion_se_llega_al_panel(): void
    {
        $this->actingAs(User::firstOrFail())
            ->get(route('login'))
            ->assertRedirect(route('panel.index'));
    }

    // ---------- Aislamiento ----------

    public function test_abrir_el_panel_no_escribe_nada(): void
    {
        $antes = [
            'socios' => Socio::count(),
            'cargos' => Cargo::count(),
            'suma_cargos' => (float) Cargo::sum('monto'),
            'pagos' => Pago::count(),
            'suma_pagos' => (float) Pago::sum('valor'),
            'partidos' => Partido::count(),
            'equipos' => Equipo::count(),
        ];

        $this->actingAs(User::firstOrFail())->get(route('panel.index'))->assertOk();
        $this->actingAs(User::firstOrFail())
            ->get(route('panel.index', ['desde' => '2020-01-01', 'hasta' => '2030-12-31']))
            ->assertOk();

        $this->assertEquals($antes, [
            'socios' => Socio::count(),
            'cargos' => Cargo::count(),
            'suma_cargos' => (float) Cargo::sum('monto'),
            'pagos' => Pago::count(),
            'suma_pagos' => (float) Pago::sum('valor'),
            'partidos' => Partido::count(),
            'equipos' => Equipo::count(),
        ], 'El panel solo lee.');
    }
}
