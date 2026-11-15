<?php

namespace Tests\Feature;

use App\Models\Cargo;
use App\Models\Equipo;
use App\Models\Pago;
use App\Models\Socio;
use App\Models\TipoCargo;
use App\Models\User;
use App\Services\SocioInactivoService;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Deteccion de socios que llevan mucho sin pagarle a la corporacion.
 *
 * Todas las pruebas trabajan dentro de una transaccion que se deshace:
 * la base local tiene datos reales y nada de esto puede quedar escrito.
 */
class SociosInactivosTest extends TestCase
{
    private function admin(): User
    {
        return User::where('role', User::ROLE_ADMIN)->firstOrFail();
    }

    private function directivo(): User
    {
        return User::create([
            'name' => 'Directivo inactivos',
            'email' => 'dirinac'.uniqid().'@corvepatios.com',
            'password' => 'secreto-de-prueba',
            'role' => User::ROLE_DIRECTIVO,
        ]);
    }

    private function socio(string $estado = Socio::ESTADO_ACTIVO, ?string $ingreso = '2020-01-01'): Socio
    {
        return Socio::create([
            'nombre_completo' => 'SOCIO INACTIVO '.uniqid(),
            'numero_documento' => uniqid('i'),
            'fecha_nacimiento' => '1990-01-01',
            'fecha_ingreso' => $ingreso,
            'entidad_salud' => 'X',
            'celular' => '3000000000',
            'tipo_sangre' => 'O+',
            'direccion_residencia' => 'X',
            'posicion_juego' => 'Medio',
            'nivel_jugador' => 2,
            'estado' => $estado,
        ]);
    }

    private function pago(Socio $socio, string $fecha, float $valor = 10000): Pago
    {
        return Pago::create([
            'socio_id' => $socio->id,
            'valor' => $valor,
            'fecha' => $fecha,
            'tipo' => 'efectivo',
        ]);
    }

    /** Busca a un socio dentro del resultado del servicio. */
    private function filaDe(Socio $socio, ?int $meses = null): ?array
    {
        return app(SocioInactivoService::class)->detectar($meses)
            ->firstWhere('id', $socio->id);
    }

    // ---------- Que entra y que no ----------

    public function test_entra_el_socio_activo_que_lleva_mas_del_limite_sin_pagar(): void
    {
        DB::beginTransaction();
        $socio = $this->socio(Socio::ESTADO_ACTIVO);
        $this->pago($socio, Carbon::today()->subMonths(7)->toDateString());

        $fila = $this->filaDe($socio);

        $this->assertNotNull($fila, 'Siete meses sin pagar debe entrar en la lista.');
        $this->assertFalse($fila['nunca_ha_pagado']);
        $this->assertSame(7, $fila['meses_sin_pagar']);

        DB::rollBack();
    }

    public function test_entra_tambien_el_socio_en_sostenimiento(): void
    {
        DB::beginTransaction();
        $socio = $this->socio(Socio::ESTADO_SUSPENDIDO);
        $this->pago($socio, Carbon::today()->subMonths(8)->toDateString());

        $fila = $this->filaDe($socio);

        $this->assertNotNull($fila, 'El socio en sostenimiento tambien se vigila.');
        $this->assertSame('Sostenimiento', $fila['estado_etiqueta']);

        DB::rollBack();
    }

    public function test_no_entra_el_que_pago_hace_poco(): void
    {
        DB::beginTransaction();
        $socio = $this->socio();
        $this->pago($socio, Carbon::today()->subMonths(2)->toDateString());

        $this->assertNull($this->filaDe($socio), 'Dos meses no llega al limite de cuatro.');

        DB::rollBack();
    }

    public function test_justo_en_el_limite_no_entra_y_un_dia_antes_si(): void
    {
        DB::beginTransaction();

        // Exactamente cuatro meses: todavia no se ha pasado.
        $enElLimite = $this->socio();
        $this->pago($enElLimite, Carbon::today()->subMonthsNoOverflow(4)->toDateString());

        // Un dia antes de eso: ya se paso.
        $pasado = $this->socio();
        $this->pago($pasado, Carbon::today()->subMonthsNoOverflow(4)->subDay()->toDateString());

        $this->assertNull($this->filaDe($enElLimite), 'Justo en el limite no debe avisar.');
        $this->assertNotNull($this->filaDe($pasado), 'Un dia despues del limite si avisa.');

        DB::rollBack();
    }

    public function test_el_retirado_y_el_excluido_quedan_fuera(): void
    {
        DB::beginTransaction();

        foreach ([Socio::ESTADO_RETIRADO, Socio::ESTADO_EXCLUIDO] as $estado) {
            $socio = $this->socio($estado);
            $this->pago($socio, Carbon::today()->subYears(3)->toDateString());

            $this->assertNull(
                $this->filaDe($socio),
                "Al socio {$estado} ya se le tomo una decision: avisar por el seria ruido.",
            );
        }

        DB::rollBack();
    }

    // ---------- El que nunca ha pagado ----------

    public function test_el_que_nunca_ha_pagado_entra_y_se_le_cuenta_desde_su_ingreso(): void
    {
        DB::beginTransaction();
        $socio = $this->socio(Socio::ESTADO_ACTIVO, Carbon::today()->subMonths(10)->toDateString());

        $fila = $this->filaDe($socio);

        $this->assertNotNull($fila, 'Quien nunca ha pagado es justo el caso a revisar.');
        $this->assertTrue($fila['nunca_ha_pagado']);
        $this->assertNull($fila['ultimo_pago']);
        $this->assertSame(10, $fila['meses_sin_pagar'], 'Se cuenta desde la fecha de ingreso.');

        DB::rollBack();
    }

    public function test_el_que_nunca_pago_y_no_tiene_fecha_de_ingreso_se_cuenta_desde_su_registro(): void
    {
        DB::beginTransaction();
        $socio = $this->socio(Socio::ESTADO_ACTIVO, null);

        $fila = $this->filaDe($socio);

        // Se acaba de crear, asi que todavia no llega al limite; pero no
        // puede reventar por no tener fecha de ingreso.
        $this->assertNull($fila, 'Recien registrado y sin fecha de ingreso: todavia no se avisa.');

        // Y con el corte en un mes tampoco, porque lleva cero dias.
        $this->assertNull($this->filaDe($socio, 1));

        DB::rollBack();
    }

    public function test_al_que_nunca_pago_tambien_se_le_aplica_el_limite(): void
    {
        DB::beginTransaction();
        // Entro hace un mes y todavia no ha pagado: es pronto para
        // ponerlo en una lista de candidatos a retirar.
        $nuevo = $this->socio(Socio::ESTADO_ACTIVO, Carbon::today()->subMonth()->toDateString());

        $this->assertNull(
            $this->filaDe($nuevo),
            'Un socio recien inscrito no puede salir de una vez en la lista.',
        );

        DB::rollBack();
    }

    public function test_el_socio_importado_se_mide_por_su_primer_cargo_y_no_por_la_fecha_de_importacion(): void
    {
        DB::beginTransaction();

        // El caso real de la importacion: el socio quedo registrado hoy,
        // sin fecha de ingreso, pero le vienen cobrando desde hace un
        // año. Medirlo por la fecha de registro lo dejaria fuera.
        $socio = $this->socio(Socio::ESTADO_ACTIVO, null);
        $tipo = TipoCargo::firstOrFail();

        foreach ([12, 8, 3] as $hace) {
            Cargo::create([
                'socio_id' => $socio->id, 'tipo_cargo_id' => $tipo->id,
                'monto' => 20000, 'fecha' => Carbon::today()->subMonths($hace)->toDateString(),
                'descripcion' => 'Prueba importado',
            ]);
        }

        $fila = $this->filaDe($socio);

        $this->assertNotNull($fila, 'Lleva un año con cargos y sin pagar: tiene que aparecer.');
        $this->assertTrue($fila['nunca_ha_pagado']);
        $this->assertSame(12, $fila['meses_sin_pagar'], 'Se cuenta desde el primer cargo.');

        DB::rollBack();
    }

    // ---------- Pagos con fecha futura ----------

    public function test_un_pago_fechado_adelante_no_lo_hace_pasar_por_al_dia(): void
    {
        DB::beginTransaction();
        $socio = $this->socio();
        $this->pago($socio, Carbon::today()->subMonths(9)->toDateString());
        // El caso real: un pago digitado con fecha del mes que viene.
        $this->pago($socio, Carbon::today()->addMonth()->toDateString());

        $fila = $this->filaDe($socio);

        $this->assertNotNull(
            $fila,
            'Un pago que todavia no ha ocurrido no puede tapar nueve meses sin pagar.',
        );
        $this->assertNotNull($fila['pago_futuro'], 'Y debe quedar marcado, para explicar por que aparece.');
        $this->assertSame(9, $fila['meses_sin_pagar']);

        DB::rollBack();
    }

    public function test_el_pago_de_hoy_si_cuenta(): void
    {
        DB::beginTransaction();
        $socio = $this->socio();
        $this->pago($socio, Carbon::today()->subMonths(9)->toDateString());
        $this->pago($socio, Carbon::today()->toDateString());

        $this->assertNull($this->filaDe($socio), 'Un pago de hoy deja al socio al dia.');

        DB::rollBack();
    }

    // ---------- Lo que muestra la fila ----------

    public function test_la_fila_trae_la_deuda_del_socio(): void
    {
        DB::beginTransaction();
        $socio = $this->socio();
        $tipo = TipoCargo::firstOrFail();

        Cargo::create([
            'socio_id' => $socio->id, 'tipo_cargo_id' => $tipo->id,
            'monto' => 50000, 'fecha' => Carbon::today()->subMonths(10)->toDateString(),
            'descripcion' => 'Prueba inactivos',
        ]);
        $this->pago($socio, Carbon::today()->subMonths(9)->toDateString(), 20000);

        $fila = $this->filaDe($socio);

        $this->assertNotNull($fila);
        $this->assertEqualsWithDelta(30000, $fila['deuda'], 0.01, 'Deuda = cargos menos pagos.');

        DB::rollBack();
    }

    public function test_la_lista_va_del_que_mas_lleva_al_que_menos(): void
    {
        DB::beginTransaction();
        $viejo = $this->socio();
        $this->pago($viejo, Carbon::today()->subMonths(20)->toDateString());
        $reciente = $this->socio();
        $this->pago($reciente, Carbon::today()->subMonths(5)->toDateString());

        $lista = app(SocioInactivoService::class)->detectar();
        $posViejo = $lista->search(fn ($f) => $f['id'] === $viejo->id);
        $posReciente = $lista->search(fn ($f) => $f['id'] === $reciente->id);

        $this->assertNotFalse($posViejo);
        $this->assertNotFalse($posReciente);
        $this->assertLessThan($posReciente, $posViejo, 'El que mas lleva sin pagar va primero.');

        DB::rollBack();
    }

    public function test_contar_coincide_con_el_largo_de_la_lista(): void
    {
        DB::beginTransaction();
        $socio = $this->socio();
        $this->pago($socio, Carbon::today()->subMonths(7)->toDateString());

        $servicio = app(SocioInactivoService::class);

        $this->assertSame(
            $servicio->detectar()->count(),
            $servicio->contar(),
            'El conteo rapido del panel y la lista deben dar lo mismo.',
        );

        DB::rollBack();
    }

    // ---------- El corte en meses ----------

    public function test_se_puede_mirar_con_otro_corte(): void
    {
        DB::beginTransaction();
        $socio = $this->socio();
        $this->pago($socio, Carbon::today()->subMonths(7)->toDateString());

        $this->assertNotNull($this->filaDe($socio, 6), 'Con corte de 6 meses, siete entra.');
        $this->assertNull($this->filaDe($socio, 12), 'Con corte de 12 meses, siete no entra.');

        DB::rollBack();
    }

    public function test_un_corte_disparatado_se_acota_en_vez_de_reventar(): void
    {
        DB::beginTransaction();
        $servicio = app(SocioInactivoService::class);

        // 999 se acota a 60 y 0 se acota a 1: la pantalla no puede caerse
        // porque alguien escriba cualquier cosa en la direccion.
        $this->assertSame($servicio->detectar(60)->count(), $servicio->detectar(999)->count());
        $this->assertSame($servicio->detectar(1)->count(), $servicio->detectar(0)->count());

        DB::rollBack();
    }

    // ---------- La pantalla ----------

    public function test_la_pantalla_pide_sesion(): void
    {
        $this->get(route('socios-inactivos.index'))->assertRedirect(route('login'));
    }

    public function test_el_administrador_ve_la_lista_y_el_boton_de_retirar(): void
    {
        DB::beginTransaction();
        $socio = $this->socio();
        $this->pago($socio, Carbon::today()->subMonths(9)->toDateString());

        // Se filtra por documento para no depender de en que pagina caiga
        // el socio: la lista va paginada y la base de pruebas tiene datos
        // reales alrededor.
        $html = $this->actingAs($this->admin())
            ->get(route('socios-inactivos.index', ['q' => $socio->numero_documento]))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString($socio->nombre_completo, $html);
        $this->assertStringContainsString('>Retirar<', $html);

        DB::rollBack();
    }

    public function test_el_directivo_ve_la_lista_pero_no_puede_retirar(): void
    {
        DB::beginTransaction();
        $socio = $this->socio();
        $this->pago($socio, Carbon::today()->subMonths(9)->toDateString());

        $html = $this->actingAs($this->directivo())
            ->get(route('socios-inactivos.index', ['q' => $socio->numero_documento]))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString($socio->nombre_completo, $html, 'El directivo si puede revisar.');
        $this->assertStringNotContainsString('>Retirar<', $html, 'Pero no puede retirar a nadie.');

        DB::rollBack();
    }

    public function test_el_directivo_no_puede_cambiar_el_estado_aunque_arme_la_peticion(): void
    {
        DB::beginTransaction();
        $socio = $this->socio();

        $this->actingAs($this->directivo())
            ->patch(route('socios.estado', $socio), ['estado' => Socio::ESTADO_RETIRADO]);

        $this->assertSame(
            Socio::ESTADO_ACTIVO,
            $socio->fresh()->estado,
            'La pantalla le esconde el boton, pero la ruta tambien debe cerrarse.',
        );

        DB::rollBack();
    }

    public function test_retirar_desde_la_lista_saca_al_socio_de_la_lista(): void
    {
        DB::beginTransaction();
        $socio = $this->socio();
        $this->pago($socio, Carbon::today()->subMonths(9)->toDateString());

        $this->assertNotNull($this->filaDe($socio));

        $this->actingAs($this->admin())
            ->patch(route('socios.estado', $socio), ['estado' => Socio::ESTADO_RETIRADO]);

        $this->assertSame(Socio::ESTADO_RETIRADO, $socio->fresh()->estado);
        $this->assertNull($this->filaDe($socio), 'Ya decidido, deja de aparecer para revisar.');

        DB::rollBack();
    }

    public function test_la_pantalla_acepta_otro_corte_por_la_direccion(): void
    {
        DB::beginTransaction();
        $socio = $this->socio();
        $this->pago($socio, Carbon::today()->subMonths(7)->toDateString());

        $doc = $socio->numero_documento;

        $conSeis = $this->actingAs($this->admin())
            ->get(route('socios-inactivos.index', ['meses' => 6, 'q' => $doc]))->assertOk()->getContent();
        $this->assertStringContainsString($socio->nombre_completo, $conSeis);

        $conDoce = $this->actingAs($this->admin())
            ->get(route('socios-inactivos.index', ['meses' => 12, 'q' => $doc]))->assertOk()->getContent();
        $this->assertStringNotContainsString($socio->nombre_completo, $conDoce);

        DB::rollBack();
    }

    public function test_un_corte_invalido_en_la_direccion_no_tumba_la_pantalla(): void
    {
        $this->actingAs($this->admin())
            ->get(route('socios-inactivos.index', ['meses' => 'hola']))
            ->assertSessionHasErrors('meses');

        $this->actingAs($this->admin())
            ->get(route('socios-inactivos.index', ['meses' => 999]))
            ->assertSessionHasErrors('meses');
    }

    // ---------- Filtros ----------

    /** Atajo: la lista filtrada, ya pasada por el servicio. */
    private function conFiltro(array $filtros, ?int $meses = null)
    {
        return app(SocioInactivoService::class)->detectar($meses, $filtros);
    }

    public function test_se_puede_buscar_por_nombre(): void
    {
        DB::beginTransaction();
        $buscado = $this->socio();
        $buscado->update(['nombre_completo' => 'ZZZBUSCADO PEREZ '.uniqid()]);
        $this->pago($buscado, Carbon::today()->subMonths(9)->toDateString());

        $otro = $this->socio();
        $this->pago($otro, Carbon::today()->subMonths(9)->toDateString());

        $ids = $this->conFiltro(['q' => 'ZZZBUSCADO'])->pluck('id');

        $this->assertTrue($ids->contains($buscado->id));
        $this->assertFalse($ids->contains($otro->id));

        DB::rollBack();
    }

    public function test_se_puede_buscar_por_documento(): void
    {
        DB::beginTransaction();
        $socio = $this->socio();
        $this->pago($socio, Carbon::today()->subMonths(9)->toDateString());

        $ids = $this->conFiltro(['q' => $socio->numero_documento])->pluck('id');

        $this->assertSame([$socio->id], $ids->all(), 'El documento es unico: debe traer uno solo.');

        DB::rollBack();
    }

    public function test_se_puede_filtrar_por_estado(): void
    {
        DB::beginTransaction();
        $activo = $this->socio(Socio::ESTADO_ACTIVO);
        $this->pago($activo, Carbon::today()->subMonths(9)->toDateString());
        $sostenimiento = $this->socio(Socio::ESTADO_SUSPENDIDO);
        $this->pago($sostenimiento, Carbon::today()->subMonths(9)->toDateString());

        $soloActivos = $this->conFiltro(['estado' => Socio::ESTADO_ACTIVO])->pluck('id');
        $this->assertTrue($soloActivos->contains($activo->id));
        $this->assertFalse($soloActivos->contains($sostenimiento->id));

        $soloSost = $this->conFiltro(['estado' => Socio::ESTADO_SUSPENDIDO])->pluck('id');
        $this->assertTrue($soloSost->contains($sostenimiento->id));
        $this->assertFalse($soloSost->contains($activo->id));

        DB::rollBack();
    }

    public function test_un_estado_que_no_se_vigila_se_ignora_en_vez_de_vaciar_la_lista(): void
    {
        DB::beginTransaction();
        $socio = $this->socio();
        $this->pago($socio, Carbon::today()->subMonths(9)->toDateString());

        // "retirado" no esta entre los vigilados: filtrar por el no puede
        // dejar la lista en cero sin avisar, se ignora y se muestra todo.
        $ids = $this->conFiltro(['estado' => Socio::ESTADO_RETIRADO])->pluck('id');

        $this->assertTrue($ids->contains($socio->id));

        DB::rollBack();
    }

    public function test_se_puede_filtrar_por_equipo(): void
    {
        DB::beginTransaction();
        $equipo = Equipo::firstOrFail();

        $conEquipo = $this->socio();
        $this->pago($conEquipo, Carbon::today()->subMonths(9)->toDateString());
        $conEquipo->equipos()->attach($equipo->id);

        $sinEquipo = $this->socio();
        $this->pago($sinEquipo, Carbon::today()->subMonths(9)->toDateString());

        $delEquipo = $this->conFiltro(['equipo_id' => (string) $equipo->id])->pluck('id');
        $this->assertTrue($delEquipo->contains($conEquipo->id));
        $this->assertFalse($delEquipo->contains($sinEquipo->id));

        DB::rollBack();
    }

    public function test_se_pueden_pedir_solo_los_que_no_tienen_equipo(): void
    {
        DB::beginTransaction();
        $equipo = Equipo::firstOrFail();

        $conEquipo = $this->socio();
        $this->pago($conEquipo, Carbon::today()->subMonths(9)->toDateString());
        $conEquipo->equipos()->attach($equipo->id);

        $sinEquipo = $this->socio();
        $this->pago($sinEquipo, Carbon::today()->subMonths(9)->toDateString());

        $ids = $this->conFiltro(['equipo_id' => SocioInactivoService::SIN_EQUIPO])->pluck('id');

        $this->assertTrue($ids->contains($sinEquipo->id));
        $this->assertFalse($ids->contains($conEquipo->id));

        DB::rollBack();
    }

    public function test_se_pueden_pedir_solo_los_que_nunca_han_pagado(): void
    {
        DB::beginTransaction();
        $nunca = $this->socio(Socio::ESTADO_ACTIVO, Carbon::today()->subMonths(10)->toDateString());
        $atrasado = $this->socio();
        $this->pago($atrasado, Carbon::today()->subMonths(9)->toDateString());

        $ids = $this->conFiltro(['solo_sin_pagos' => true])->pluck('id');

        $this->assertTrue($ids->contains($nunca->id));
        $this->assertFalse($ids->contains($atrasado->id));

        DB::rollBack();
    }

    public function test_se_pueden_pedir_solo_los_de_fecha_adelantada(): void
    {
        DB::beginTransaction();
        $conFuturo = $this->socio();
        $this->pago($conFuturo, Carbon::today()->subMonths(9)->toDateString());
        $this->pago($conFuturo, Carbon::today()->addMonth()->toDateString());

        $normal = $this->socio();
        $this->pago($normal, Carbon::today()->subMonths(9)->toDateString());

        $ids = $this->conFiltro(['con_fecha_adelantada' => true])->pluck('id');

        $this->assertTrue($ids->contains($conFuturo->id));
        $this->assertFalse($ids->contains($normal->id));

        DB::rollBack();
    }

    public function test_los_filtros_se_suman_entre_si(): void
    {
        DB::beginTransaction();
        $equipo = Equipo::firstOrFail();

        // El unico que cumple las tres condiciones a la vez.
        $elBueno = $this->socio(Socio::ESTADO_SUSPENDIDO, Carbon::today()->subMonths(10)->toDateString());
        $elBueno->equipos()->attach($equipo->id);

        // Cumple dos de tres: tiene equipo y nunca ha pagado, pero esta activo.
        $otroEstado = $this->socio(Socio::ESTADO_ACTIVO, Carbon::today()->subMonths(10)->toDateString());
        $otroEstado->equipos()->attach($equipo->id);

        // Cumple dos de tres: en sostenimiento y nunca ha pagado, sin equipo.
        $sinEquipo = $this->socio(Socio::ESTADO_SUSPENDIDO, Carbon::today()->subMonths(10)->toDateString());

        $ids = $this->conFiltro([
            'estado' => Socio::ESTADO_SUSPENDIDO,
            'equipo_id' => (string) $equipo->id,
            'solo_sin_pagos' => true,
        ])->pluck('id');

        $this->assertTrue($ids->contains($elBueno->id));
        $this->assertFalse($ids->contains($otroEstado->id));
        $this->assertFalse($ids->contains($sinEquipo->id));

        DB::rollBack();
    }

    public function test_el_filtro_no_puede_sacar_a_quien_no_cumple_el_corte(): void
    {
        DB::beginTransaction();
        // Pago reciente: no esta en la lista, y buscarlo por nombre
        // tampoco debe meterlo.
        $alDia = $this->socio();
        $alDia->update(['nombre_completo' => 'ZZZALDIA '.uniqid()]);
        $this->pago($alDia, Carbon::today()->subMonth()->toDateString());

        $this->assertCount(
            0,
            $this->conFiltro(['q' => 'ZZZALDIA']),
            'El filtro recorta la lista, no la amplia.',
        );

        DB::rollBack();
    }

    public function test_el_conteo_del_panel_no_se_deja_tocar_por_los_filtros(): void
    {
        DB::beginTransaction();
        $socio = $this->socio();
        $this->pago($socio, Carbon::today()->subMonths(9)->toDateString());

        $servicio = app(SocioInactivoService::class);
        $total = $servicio->contar();
        $filtrados = $servicio->detectar(null, ['q' => $socio->numero_documento])->count();

        $this->assertSame(1, $filtrados);
        $this->assertGreaterThanOrEqual($filtrados, $total);
        $this->assertSame($total, $servicio->contar(), 'El aviso del panel siempre cuenta el total.');

        DB::rollBack();
    }

    // ---------- Los filtros en la pantalla ----------

    public function test_la_pantalla_filtra_y_dice_cuantos_hay_en_total(): void
    {
        DB::beginTransaction();
        $buscado = $this->socio();
        $buscado->update(['nombre_completo' => 'ZZZFILTRADO LOPEZ']);
        $this->pago($buscado, Carbon::today()->subMonths(9)->toDateString());

        $otro = $this->socio();
        $otro->update(['nombre_completo' => 'YYYOTRO GOMEZ']);
        $this->pago($otro, Carbon::today()->subMonths(9)->toDateString());

        $html = $this->actingAs($this->admin())
            ->get(route('socios-inactivos.index', ['q' => 'ZZZFILTRADO']))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('ZZZFILTRADO LOPEZ', $html);
        $this->assertStringNotContainsString('YYYOTRO GOMEZ', $html);
        $this->assertStringContainsString('Se muestran', $html, 'Debe avisar que esta viendo un recorte.');

        DB::rollBack();
    }

    public function test_la_pantalla_ofrece_los_equipos_para_filtrar(): void
    {
        DB::beginTransaction();
        $equipo = Equipo::firstOrFail();

        $html = $this->actingAs($this->admin())
            ->get(route('socios-inactivos.index'))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('name="equipo_id"', $html);
        $this->assertStringContainsString($equipo->nombre, $html);
        $this->assertStringContainsString('Sin equipo', $html);

        DB::rollBack();
    }

    public function test_un_filtro_que_no_deja_a_nadie_lo_dice_y_ofrece_volver(): void
    {
        $html = $this->actingAs($this->admin())
            ->get(route('socios-inactivos.index', ['q' => 'NOEXISTEESTESOCIOENNINGUNLADO']))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('Ningun socio cumple con ese filtro', $html);
        $this->assertStringContainsString('verlos todos', $html);
    }

    public function test_un_estado_invalido_en_la_direccion_se_rechaza(): void
    {
        $this->actingAs($this->admin())
            ->get(route('socios-inactivos.index', ['estado' => 'inventado']))
            ->assertSessionHasErrors('estado');
    }

    public function test_un_equipo_que_no_es_numero_no_tumba_la_pantalla(): void
    {
        DB::beginTransaction();
        $socio = $this->socio();
        $this->pago($socio, Carbon::today()->subMonths(9)->toDateString());

        // Se ignora en silencio: es un filtro de revision, no un permiso.
        $html = $this->actingAs($this->admin())
            ->get(route('socios-inactivos.index', ['equipo_id' => 'abc', 'q' => $socio->numero_documento]))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString($socio->nombre_completo, $html);

        DB::rollBack();
    }

    // ---------- Paginacion ----------

    public function test_la_pagina_trae_como_mucho_las_filas_del_tope(): void
    {
        $pagina = app(SocioInactivoService::class)->paginar();

        $this->assertLessThanOrEqual(SocioInactivoService::POR_PAGINA, $pagina->count());
        $this->assertSame(SocioInactivoService::POR_PAGINA, $pagina->perPage());
    }

    public function test_el_total_del_paginador_es_el_de_toda_la_lista_no_el_de_la_pagina(): void
    {
        DB::beginTransaction();
        $socio = $this->socio();
        $this->pago($socio, Carbon::today()->subMonths(9)->toDateString());

        $servicio = app(SocioInactivoService::class);

        $this->assertSame(
            $servicio->detectar()->count(),
            $servicio->paginar()->total(),
            'El paginador debe saber cuantos hay en total, no cuantos trae.',
        );

        DB::rollBack();
    }

    public function test_ningun_socio_se_repite_ni_se_pierde_entre_paginas(): void
    {
        DB::beginTransaction();

        // Varios socios con la MISMA fecha: es el caso en que un orden sin
        // desempate deja a alguien fuera o lo muestra dos veces.
        $misma = Carbon::today()->subMonths(9)->toDateString();
        for ($i = 0; $i < 6; $i++) {
            $this->pago($this->socio(), $misma);
        }

        $servicio = app(SocioInactivoService::class);
        $completa = $servicio->detectar()->pluck('id');

        $ultima = $servicio->paginar(null, [], 5)->lastPage();
        $recorridas = collect();

        for ($n = 1; $n <= $ultima; $n++) {
            Paginator::currentPageResolver(fn () => $n);
            $recorridas = $recorridas->merge(
                collect($servicio->paginar(null, [], 5)->items())->pluck('id'),
            );
        }
        Paginator::currentPageResolver(fn () => 1);

        $this->assertSame(
            $completa->sort()->values()->all(),
            $recorridas->sort()->values()->all(),
            'Recorrer todas las paginas debe dar exactamente la lista completa.',
        );
        $this->assertSame(
            $recorridas->count(),
            $recorridas->unique()->count(),
            'Ningun socio puede salir en dos paginas.',
        );

        DB::rollBack();
    }

    public function test_la_pantalla_conserva_los_filtros_al_cambiar_de_pagina(): void
    {
        $html = $this->actingAs($this->admin())
            ->get(route('socios-inactivos.index', ['meses' => 1]))
            ->assertOk()
            ->getContent();

        // Con el corte en un mes hay de sobra para que haya mas de una
        // pagina, y los enlaces deben arrastrar el filtro.
        $this->assertStringContainsString('meses=1', $html, 'Los enlaces de pagina deben conservar el filtro.');
    }

    public function test_los_totales_son_del_conjunto_entero_y_no_de_la_pagina(): void
    {
        $servicio = app(SocioInactivoService::class);
        $resumen = $servicio->resumen(1);
        $pagina = $servicio->paginar(1);

        $this->assertSame($pagina->total(), $resumen['total']);
        $this->assertGreaterThan(
            $pagina->count(),
            $resumen['total'],
            'Con el corte en un mes hay mas socios que los que caben en una pagina.',
        );

        // La deuda del resumen tiene que ser la de todos, no la de los 25.
        $deudaDeLaPagina = collect($pagina->items())->sum(fn ($f) => max(0, $f['deuda']));
        $this->assertGreaterThan($deudaDeLaPagina, $resumen['deuda']);
    }

    // ---------- El aviso del panel ----------

    public function test_el_panel_avisa_y_enlaza_a_la_lista(): void
    {
        DB::beginTransaction();
        $socio = $this->socio();
        $this->pago($socio, Carbon::today()->subMonths(9)->toDateString());

        $html = $this->actingAs($this->admin())
            ->get(route('panel.index'))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('Socios sin pagar hace 4 meses o mas', $html);
        $this->assertStringContainsString(route('socios-inactivos.index'), $html);

        DB::rollBack();
    }
}
