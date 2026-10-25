<?php

namespace Tests\Feature;

use App\Models\AjustePuntos;
use App\Models\Cargo;
use App\Models\CierreCaja;
use App\Models\Configuracion;
use App\Models\CuentaBancaria;
use App\Models\Equipo;
use App\Models\IncidenciaPartido;
use App\Models\Noticia;
use App\Models\NoticiaAdjunto;
use App\Models\Pago;
use App\Models\Partido;
use App\Models\Socio;
use App\Models\TipoCargo;
use App\Models\Torneo;
use App\Models\User;
use App\Services\CargoService;
use App\Services\CuotaModeradaService;
use App\Services\EquipoService;
use App\Services\NoticiaService;
use App\Services\PagoService;
use App\Services\RegistroPartidoService;
use App\Services\SocioService;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Comprueba que cada modulo solo toque lo suyo.
 *
 * El metodo es el mismo en todas las pruebas: se fotografia el estado de
 * TODAS las tablas, se ejecuta una operacion de un modulo, y se verifica
 * que solo hayan cambiado las tablas que esa operacion debe tocar. Si un
 * modulo estuviera afectando a otro sin tener nada que ver, aqui salta.
 */
class AislamientoModulosTest extends TestCase
{
    /** Fotografia del sistema completo: conteos y sumas de dinero. */
    private function foto(): array
    {
        return [
            'socios' => Socio::count(),
            'socios_por_estado' => Socio::selectRaw('estado, count(*) c')->groupBy('estado')->pluck('c', 'estado')->toArray(),
            'socios_por_carnet' => Socio::selectRaw('carnet, count(*) c')->groupBy('carnet')->pluck('c', 'carnet')->toArray(),
            'cuotas_moderadas' => (float) Socio::sum('cuota_moderada'),
            'equipos' => Equipo::count(),
            'equipo_socio' => DB::table('equipo_socio')->count(),
            'torneos' => Torneo::count(),
            'tipos_cargo' => TipoCargo::count(),
            'cargos_n' => Cargo::count(),
            'cargos_suma' => (float) Cargo::sum('monto'),
            'pagos_n' => Pago::count(),
            'pagos_suma' => (float) Pago::sum('valor'),
            'partidos' => Partido::count(),
            'incidencias' => IncidenciaPartido::count(),
            'ajustes_puntos' => AjustePuntos::count(),
            'noticias' => Noticia::count(),
            'adjuntos' => NoticiaAdjunto::count(),
            'cierres_caja' => CierreCaja::count(),
            'configuraciones' => Configuracion::count(),
            'cuentas_bancarias' => CuentaBancaria::count(),
            'usuarios' => User::count(),
        ];
    }

    /**
     * Falla si cambio alguna tabla que no estuviera en la lista esperada.
     *
     * @param  array<int, string>  $esperadas
     */
    private function soloCambiaron(array $antes, array $esperadas, string $operacion): void
    {
        $despues = $this->foto();
        $cambiadas = [];

        foreach ($antes as $clave => $valor) {
            if ($despues[$clave] !== $valor) {
                $cambiadas[] = $clave;
            }
        }

        $inesperadas = array_diff($cambiadas, $esperadas);

        $this->assertEmpty(
            $inesperadas,
            "\"{$operacion}\" toco tablas que no le corresponden: ".implode(', ', $inesperadas)
        );
    }

    private function socioNuevo(string $nombre, ?Equipo $equipo = null): Socio
    {
        $socio = Socio::create([
            'nombre_completo' => $nombre, 'numero_documento' => uniqid('v'),
            'fecha_nacimiento' => '1990-01-01', 'entidad_salud' => 'X', 'celular' => '300',
            'tipo_sangre' => 'O+', 'direccion_residencia' => 'X', 'posicion_juego' => 'Medio',
            'nivel_jugador' => 2, 'estado' => 'activo',
        ]);

        if ($equipo) {
            $socio->equipos()->attach($equipo->id);
        }

        return $socio;
    }

    private function torneoConDosEquipos(): array
    {
        $torneo = Torneo::create(['nombre' => 'Torneo aislamiento '.uniqid(), 'fecha_inicio' => '2030-01-01']);
        $a = Equipo::create(['nombre' => 'Aisla A '.uniqid(), 'torneo_id' => $torneo->id, 'estado' => 'activo']);
        $b = Equipo::create(['nombre' => 'Aisla B '.uniqid(), 'torneo_id' => $torneo->id, 'estado' => 'activo']);

        return [$torneo, $a, $b];
    }

    // ---------- Competencia no toca finanzas ----------

    public function test_programar_un_partido_no_toca_nada_de_dinero(): void
    {
        DB::beginTransaction();
        [$torneo, $a, $b] = $this->torneoConDosEquipos();
        $antes = $this->foto();

        $this->actingAs(User::firstOrFail())->post(route('partidos.store'), [
            'torneo_id' => $torneo->id, 'equipo_local_id' => $a->id, 'equipo_visitante_id' => $b->id,
            'fecha_hora' => '2030-05-01T16:00', 'estado' => Partido::ESTADO_PROGRAMADO,
        ])->assertRedirect();

        $this->soloCambiaron($antes, ['partidos'], 'Programar un partido');
        DB::rollBack();
    }

    public function test_la_tabla_de_posiciones_solo_lee(): void
    {
        DB::beginTransaction();
        [$torneo, $a, $b] = $this->torneoConDosEquipos();
        Partido::create([
            'torneo_id' => $torneo->id, 'equipo_local_id' => $a->id, 'equipo_visitante_id' => $b->id,
            'goles_local' => 2, 'goles_visitante' => 1,
            'fecha_hora' => '2030-02-01 10:00', 'estado' => Partido::ESTADO_JUGADO,
        ]);
        $antes = $this->foto();

        $this->actingAs(User::firstOrFail())->get(route('partidos.posiciones', ['torneo_id' => $torneo->id]))->assertOk();
        app(\App\Services\TablaPosicionesService::class)->calcular($torneo);

        $this->soloCambiaron($antes, [], 'Ver la tabla de posiciones');
        DB::rollBack();
    }

    public function test_el_ajuste_de_puntos_no_toca_socios_ni_dinero(): void
    {
        DB::beginTransaction();
        [$torneo, $a] = $this->torneoConDosEquipos();
        $antes = $this->foto();

        $this->actingAs(User::firstOrFail())->post(route('partidos.ajustes.store'), [
            'torneo_id' => $torneo->id, 'equipo_id' => $a->id,
            'puntos' => -3, 'motivo' => 'Prueba', 'fecha' => '2030-02-05',
        ])->assertRedirect();

        $this->soloCambiaron($antes, ['ajustes_puntos'], 'Ajustar puntos');
        DB::rollBack();
    }

    /**
     * Esta es la unica conexion querida entre competencia y finanzas: una
     * tarjeta genera un cargo. Debe tocar partido, incidencias y cargos,
     * y nada mas (ni pagos, ni estados de socio, ni cuotas).
     */
    public function test_registrar_resultado_solo_toca_incidencias_y_cargos(): void
    {
        DB::beginTransaction();
        [$torneo, $a, $b] = $this->torneoConDosEquipos();
        $socio = $this->socioNuevo('AISLA JUGADOR', $a);
        $partido = Partido::create([
            'torneo_id' => $torneo->id, 'equipo_local_id' => $a->id, 'equipo_visitante_id' => $b->id,
            'fecha_hora' => '2030-02-01 10:00', 'estado' => Partido::ESTADO_PROGRAMADO,
        ]);
        $antes = $this->foto();

        app(RegistroPartidoService::class)->registrar($partido, [
            ['socio_id' => $socio->id, 'equipo_id' => $a->id, 'tipo' => IncidenciaPartido::TIPO_GOL],
            ['socio_id' => $socio->id, 'equipo_id' => $a->id, 'tipo' => IncidenciaPartido::TIPO_AMARILLA],
        ]);

        $this->soloCambiaron($antes, ['incidencias', 'cargos_n', 'cargos_suma'], 'Registrar resultado');

        // Y que el estado del socio no se vea alterado por una tarjeta.
        $this->assertEquals('activo', $socio->fresh()->estado, 'Una tarjeta no debe cambiar el estado del socio.');
        DB::rollBack();
    }

    // ---------- Finanzas no toca competencia ----------

    public function test_los_pagos_multiples_no_tocan_partidos_ni_socios(): void
    {
        DB::beginTransaction();
        [$torneo, $a] = $this->torneoConDosEquipos();
        $socio = $this->socioNuevo('AISLA PAGADOR', $a);
        $antes = $this->foto();

        $this->actingAs(User::firstOrFail())->postJson(route('equipos.pagos.ejecutar', $a), [
            'fecha' => '2026-02-10',
            'pagos' => [['socio_id' => $socio->id, 'valor' => 10000, 'tipo' => 'efectivo']],
        ])->assertOk();

        $this->soloCambiaron($antes, ['pagos_n', 'pagos_suma'], 'Pagos multiples');
        DB::rollBack();
    }

    public function test_el_cobro_masivo_no_toca_partidos_ni_pagos(): void
    {
        DB::beginTransaction();
        [$torneo, $a] = $this->torneoConDosEquipos();
        $this->socioNuevo('AISLA COBRADO', $a);
        $antes = $this->foto();

        app(CargoService::class)->aplicarMasivo(
            TipoCargo::where('nombre', 'Mensualidad')->firstOrFail(),
            '2030-07-01', null, 'equipo', $a->id
        );

        $this->soloCambiaron($antes, ['cargos_n', 'cargos_suma'], 'Cobro masivo por equipo');
        DB::rollBack();
    }

    public function test_el_recalculo_de_cuota_moderada_solo_toca_esa_columna(): void
    {
        DB::beginTransaction();
        $antes = $this->foto();

        app(CuotaModeradaService::class)->recalcularTodas();

        $this->soloCambiaron($antes, ['cuotas_moderadas'], 'Recalcular cuota moderada');
        DB::rollBack();
    }

    public function test_el_cierre_de_caja_no_altera_pagos_ni_cargos(): void
    {
        DB::beginTransaction();
        $antes = $this->foto();

        $this->actingAs(User::firstOrFail())->post(route('cierre-caja.store'), [
            'fecha' => '2030-09-09', 'notas' => 'Prueba de aislamiento',
        ])->assertRedirect();

        $this->soloCambiaron($antes, ['cierres_caja'], 'Cierre de caja');
        DB::rollBack();
    }

    // ---------- Socios y equipos ----------

    public function test_cambiar_el_carnet_no_toca_nada_mas(): void
    {
        DB::beginTransaction();
        $socio = Socio::firstOrFail();
        $antes = $this->foto();

        $this->actingAs(User::firstOrFail())
            ->patch(route('socios.carnet', $socio), ['carnet' => Socio::CARNET_TIENE])
            ->assertRedirect();

        $this->soloCambiaron($antes, ['socios_por_carnet'], 'Cambiar carnet');
        DB::rollBack();
    }

    public function test_quitar_a_un_socio_de_su_equipo_no_toca_su_dinero(): void
    {
        DB::beginTransaction();
        [$torneo, $a] = $this->torneoConDosEquipos();
        $socio = $this->socioNuevo('AISLA RETIRABLE', $a);
        $deudaAntes = $socio->deuda_total;
        $antes = $this->foto();

        app(EquipoService::class)->quitarSocio($a, $socio);

        // Cambia el vinculo y su estado (pasa a retirado), pero el dinero no.
        $this->soloCambiaron($antes, ['equipo_socio', 'socios_por_estado'], 'Quitar socio del equipo');
        $this->assertEquals($deudaAntes, $socio->fresh()->deuda_total, 'La deuda debe conservarse.');
        DB::rollBack();
    }

    public function test_un_socio_excluido_no_entra_en_los_cobros(): void
    {
        DB::beginTransaction();
        [$torneo, $a] = $this->torneoConDosEquipos();
        $excluido = $this->socioNuevo('AISLA EXCLUIDO', $a);
        $excluido->update(['estado' => Socio::ESTADO_EXCLUIDO]);

        app(CargoService::class)->aplicarMasivo(
            TipoCargo::where('nombre', 'Mensualidad')->firstOrFail(),
            '2030-08-01', null, 'equipo', $a->id
        );

        $this->assertEquals(0, $excluido->fresh()->cargos()->count(), 'A un excluido no se le cobra.');
        DB::rollBack();
    }

    // ---------- Noticias y configuracion ----------

    public function test_las_noticias_no_tocan_socios_ni_dinero(): void
    {
        DB::beginTransaction();
        $antes = $this->foto();

        app(NoticiaService::class)->crear(
            ['titulo' => 'Aislamiento', 'contenido' => 'x', 'fecha_publicacion' => '2030-01-01', 'publicado' => true],
            null, User::firstOrFail()->id
        );

        $this->soloCambiaron($antes, ['noticias'], 'Publicar una noticia');
        DB::rollBack();
    }

    public function test_cambiar_la_configuracion_no_toca_datos_operativos(): void
    {
        DB::beginTransaction();
        $antes = $this->foto();

        Configuracion::guardar(Configuracion::NOMBRE_SISTEMA, 'Nombre de prueba');

        $this->soloCambiaron($antes, ['configuraciones'], 'Guardar configuracion');
        DB::rollBack();
    }

    public function test_registrar_un_socio_no_toca_partidos_ni_noticias(): void
    {
        DB::beginTransaction();
        [$torneo, $a] = $this->torneoConDosEquipos();
        $antes = $this->foto();

        app(SocioService::class)->crear([
            'nombre_completo' => 'AISLA NUEVO', 'numero_documento' => uniqid('n'),
            'fecha_nacimiento' => '1990-01-01', 'entidad_salud' => 'X', 'celular' => '300',
            'tipo_sangre' => 'O+', 'direccion_residencia' => 'X', 'posicion_juego' => 'Medio',
            'nivel_jugador' => 2, 'estado' => 'activo',
        ], [], $a->id);

        $this->soloCambiaron($antes, ['socios', 'socios_por_estado', 'socios_por_carnet', 'equipo_socio'], 'Registrar socio');
        DB::rollBack();
    }

    // ---------- Reportes: no deben escribir nunca ----------

    public function test_ningun_reporte_modifica_la_base(): void
    {
        $equipo = Equipo::has('socios')->firstOrFail();
        $otro = Equipo::where('id', '!=', $equipo->id)->firstOrFail();
        $servicio = app(\App\Services\ReporteService::class);
        $antes = $this->foto();

        $servicio->socioPdf(Socio::firstOrFail())->output();
        $servicio->equipoPdf($equipo)->output();
        $servicio->planillaPagosPdf($equipo)->output();
        $servicio->planillaJuegoPdf($equipo, $otro, ['fecha' => '2026-01-01'])->output();

        $this->soloCambiaron($antes, [], 'Generar los reportes');
    }

    public function test_navegar_por_el_sistema_no_modifica_nada(): void
    {
        $socio = Socio::firstOrFail();
        $equipo = Equipo::has('socios')->firstOrFail();
        $antes = $this->foto();

        foreach ([
            route('socios.index'), route('socios.show', $socio), route('equipos.index'),
            route('equipos.show', $equipo), route('partidos.index'), route('partidos.posiciones'),
            route('torneos.index'), route('tipos-cargo.index'), route('cuota-moderada.index'),
            route('cierre-caja.index'), route('noticias-admin.index'), route('reportes-contables.index'),
            route('cargos-duplicados.index'), route('configuracion.index'), route('importar.index'),
        ] as $url) {
            $this->actingAs(User::firstOrFail())->get($url)->assertOk();
        }

        $this->soloCambiaron($antes, [], 'Navegar por el sistema');
    }
}
