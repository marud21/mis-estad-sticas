<?php

namespace Tests\Feature;

use App\Models\Equipo;
use App\Models\IncidenciaPartido;
use App\Models\Partido;
use App\Models\Socio;
use App\Models\Torneo;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

/**
 * Numero de camiseta y carnet puestos desde la planilla del partido.
 *
 * Lo que mas se cuida aqui es que esta pantalla escribe en la ficha de
 * los socios: tiene que tocar solo a los que jugaron, solo lo que de
 * verdad cambio, y nunca borrar un estado que la casilla no sabe decir.
 *
 * Cada prueba corre dentro de una transaccion que se deshace al final,
 * porque la base de este equipo tiene los datos reales del club. Va en
 * setUp/tearDown para que tambien se deshaga si una prueba falla a la
 * mitad.
 */
class NumeroYCarnetEnRegistroTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        DB::beginTransaction();
        Queue::fake();
    }

    protected function tearDown(): void
    {
        DB::rollBack();
        parent::tearDown();
    }

    private function admin(): User
    {
        return User::where('role', User::ROLE_ADMIN)->firstOrFail();
    }

    private function socio(Equipo $equipo, array $extra = []): Socio
    {
        $s = Socio::create(array_merge([
            'nombre_completo' => 'JUGADOR '.uniqid(),
            'numero_documento' => uniqid('n'),
            'fecha_nacimiento' => '1990-01-01', 'entidad_salud' => 'X', 'celular' => '300',
            'tipo_sangre' => 'O+', 'direccion_residencia' => 'X', 'posicion_juego' => 'Medio',
            'nivel_jugador' => 2, 'estado' => Socio::ESTADO_ACTIVO,
        ], $extra));
        $s->equipos()->attach($equipo->id);

        return $s;
    }

    /** @return array{0:Partido,1:Equipo,2:Equipo} */
    private function escenario(): array
    {
        $t = Torneo::create(['nombre' => 'Torneo NC '.uniqid(), 'fecha_inicio' => '2030-01-01']);
        $local = Equipo::create(['nombre' => 'NCA '.uniqid(), 'torneo_id' => $t->id, 'categoria' => '50', 'estado' => 'activo']);
        $visita = Equipo::create(['nombre' => 'NCB '.uniqid(), 'torneo_id' => $t->id, 'categoria' => '50', 'estado' => 'activo']);

        $partido = Partido::create([
            'torneo_id' => $t->id,
            'equipo_local_id' => $local->id,
            'equipo_visitante_id' => $visita->id,
            'fecha_hora' => '2030-02-01 10:00',
            'estado' => Partido::ESTADO_PROGRAMADO,
            'fase' => 'Jornada 1',
        ]);

        return [$partido, $local, $visita];
    }

    /** Una fila de la planilla tal como la manda el formulario. */
    private function fila(Socio $socio, Equipo $equipo, array $extra = []): array
    {
        return [$socio->id => array_merge(['equipo_id' => $equipo->id], $extra)];
    }

    /**
     * Junta varias filas en una planilla.
     *
     * NO se usa array_merge: con claves enteras las renumera desde cero,
     * y las claves aqui son los id de los socios. Con array_merge las
     * filas terminarian apuntando a los socios 0 y 1.
     */
    private function planilla(array ...$filas): array
    {
        return array_replace([], ...$filas);
    }

    private function guardar(Partido $partido, array $jugadores)
    {
        return $this->actingAs($this->admin())
            ->post(route('partidos.registro.guardar', $partido), ['jugadores' => $jugadores]);
    }

    // ================= LA PANTALLA =================

    public function test_la_planilla_ofrece_el_numero_y_la_casilla_de_carnet(): void
    {
        [$partido, $local] = $this->escenario();
        $socio = $this->socio($local, ['numero_camiseta' => 7]);

        $html = $this->actingAs($this->admin())
            ->get(route('partidos.registro', $partido))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString("jugadores[{$socio->id}][numero]", $html);
        $this->assertStringContainsString("jugadores[{$socio->id}][carnet]", $html);
        $this->assertStringContainsString('Carnet', $html);
    }

    public function test_la_planilla_llega_con_lo_que_el_socio_ya_tiene(): void
    {
        [$partido, $local] = $this->escenario();
        $conCarnet = $this->socio($local, ['numero_camiseta' => 10, 'carnet' => Socio::CARNET_TIENE]);
        $sinCarnet = $this->socio($local, ['carnet' => Socio::CARNET_NO_TIENE]);

        $html = $this->actingAs($this->admin())
            ->get(route('partidos.registro', $partido))
            ->assertOk()
            ->getContent();

        // El numero que ya tenia viene escrito en su casilla.
        $this->assertMatchesRegularExpression(
            '#name="jugadores\['.$conCarnet->id.'\]\[numero\]"[^>]*value="10"#',
            $html,
        );
        // Y su casilla de carnet viene marcada; la del otro no.
        $this->assertMatchesRegularExpression(
            '#name="jugadores\['.$conCarnet->id.'\]\[carnet\]"[^>]*checked#',
            $html,
        );
        $this->assertDoesNotMatchRegularExpression(
            '#name="jugadores\['.$sinCarnet->id.'\]\[carnet\]"[^>]*checked#',
            $html,
        );
    }

    // ================= GUARDAR =================

    public function test_guarda_el_numero_en_la_ficha_del_socio(): void
    {
        [$partido, $local] = $this->escenario();
        $socio = $this->socio($local);

        $this->guardar($partido, $this->fila($socio, $local, ['numero' => 23]))
            ->assertRedirect();

        $this->assertSame(23, (int) $socio->fresh()->numero_camiseta);
    }

    public function test_marcar_la_casilla_le_pone_el_carnet(): void
    {
        [$partido, $local] = $this->escenario();
        $socio = $this->socio($local, ['carnet' => Socio::CARNET_NO_TIENE]);

        $this->guardar($partido, $this->fila($socio, $local, ['carnet' => '1']));

        $this->assertSame(Socio::CARNET_TIENE, $socio->fresh()->carnet);
    }

    public function test_dejar_la_casilla_sin_marcar_le_quita_el_carnet(): void
    {
        [$partido, $local] = $this->escenario();
        $socio = $this->socio($local, ['carnet' => Socio::CARNET_TIENE]);

        // Sin la clave "carnet": es como viaja una casilla sin marcar.
        $this->guardar($partido, $this->fila($socio, $local));

        $this->assertSame(Socio::CARNET_NO_TIENE, $socio->fresh()->carnet);
    }

    public function test_al_extraviado_no_se_le_borra_ese_estado(): void
    {
        [$partido, $local] = $this->escenario();
        $socio = $this->socio($local, ['carnet' => Socio::CARNET_EXTRAVIADO]);

        $this->guardar($partido, $this->fila($socio, $local));

        $this->assertSame(
            Socio::CARNET_EXTRAVIADO,
            $socio->fresh()->carnet,
            'La casilla no sabe decir "extraviado", asi que dejarla sin marcar no puede borrarlo.',
        );
    }

    public function test_al_extraviado_si_se_le_puede_poner_que_ya_lo_tiene(): void
    {
        [$partido, $local] = $this->escenario();
        $socio = $this->socio($local, ['carnet' => Socio::CARNET_EXTRAVIADO]);

        $this->guardar($partido, $this->fila($socio, $local, ['carnet' => '1']));

        $this->assertSame(Socio::CARNET_TIENE, $socio->fresh()->carnet);
    }

    public function test_dejar_el_numero_vacio_se_lo_quita(): void
    {
        [$partido, $local] = $this->escenario();
        $socio = $this->socio($local, ['numero_camiseta' => 9]);

        $this->guardar($partido, $this->fila($socio, $local, ['numero' => '']));

        $this->assertNull($socio->fresh()->numero_camiseta);
    }

    public function test_guarda_el_numero_de_los_dos_equipos(): void
    {
        [$partido, $local, $visita] = $this->escenario();
        $uno = $this->socio($local);
        $otro = $this->socio($visita);

        $this->guardar($partido, $this->planilla(
            $this->fila($uno, $local, ['numero' => 1]),
            $this->fila($otro, $visita, ['numero' => 2]),
        ));

        $this->assertSame(1, (int) $uno->fresh()->numero_camiseta);
        $this->assertSame(2, (int) $otro->fresh()->numero_camiseta);
    }

    // ================= LO QUE NO DEBE TOCAR =================

    public function test_no_le_toca_la_ficha_a_un_socio_que_no_jugo(): void
    {
        [$partido, $local] = $this->escenario();
        $jugador = $this->socio($local);

        // Un socio de otro equipo, que no tiene nada que ver con este
        // partido, colado en el envio con su id.
        $otroTorneo = Torneo::create(['nombre' => 'Otro '.uniqid(), 'fecha_inicio' => '2030-01-01']);
        $otroEquipo = Equipo::create(['nombre' => 'NCX '.uniqid(), 'torneo_id' => $otroTorneo->id, 'categoria' => '50', 'estado' => 'activo']);
        $ajeno = $this->socio($otroEquipo, ['numero_camiseta' => 5, 'carnet' => Socio::CARNET_NO_TIENE]);

        $this->guardar($partido, $this->planilla(
            $this->fila($jugador, $local, ['numero' => 11]),
            [$ajeno->id => ['equipo_id' => $local->id, 'numero' => 99, 'carnet' => '1']],
        ));

        $ajeno->refresh();
        $this->assertSame(5, (int) $ajeno->numero_camiseta, 'Al ajeno no se le cambio el numero.');
        $this->assertSame(Socio::CARNET_NO_TIENE, $ajeno->carnet, 'Ni el carnet.');
        $this->assertSame(11, (int) $jugador->fresh()->numero_camiseta, 'Al que si jugo si.');
    }

    public function test_no_mueve_la_fecha_de_actualizacion_de_quien_no_cambio(): void
    {
        [$partido, $local] = $this->escenario();
        $socio = $this->socio($local, ['numero_camiseta' => 8, 'carnet' => Socio::CARNET_TIENE]);

        DB::table('socios')->where('id', $socio->id)
            ->update(['updated_at' => '2020-01-01 00:00:00']);

        // Se manda exactamente lo que ya tenia.
        $this->guardar($partido, $this->fila($socio, $local, ['numero' => 8, 'carnet' => '1']));

        $this->assertSame(
            '2020-01-01 00:00:00',
            (string) DB::table('socios')->where('id', $socio->id)->value('updated_at'),
            'Si no cambio nada, no se escribe la ficha.',
        );
    }

    public function test_no_toca_los_demas_datos_del_socio(): void
    {
        [$partido, $local] = $this->escenario();
        $socio = $this->socio($local, ['numero_camiseta' => 3]);
        $antes = $socio->only(['nombre_completo', 'numero_documento', 'estado', 'celular', 'posicion_juego', 'nivel_jugador']);

        $this->guardar($partido, $this->fila($socio, $local, ['numero' => 4, 'carnet' => '1']));

        $this->assertSame($antes, $socio->fresh()->only(array_keys($antes)));
    }

    public function test_el_walkover_no_cambia_numeros_ni_carnets(): void
    {
        [$partido, $local, $visita] = $this->escenario();
        $socio = $this->socio($local, ['numero_camiseta' => 6, 'carnet' => Socio::CARNET_NO_TIENE]);

        $this->actingAs($this->admin())->post(route('partidos.registro.guardar', $partido), [
            'walkover' => '1',
            'equipo_ausente_id' => $visita->id,
            'goles_local' => 3,
            'goles_visitante' => 0,
        ])->assertRedirect();

        $socio->refresh();
        $this->assertSame(6, (int) $socio->numero_camiseta);
        $this->assertSame(Socio::CARNET_NO_TIENE, $socio->carnet);
    }

    // ================= VALIDACION =================

    public function test_rechaza_un_numero_fuera_de_rango(): void
    {
        [$partido, $local] = $this->escenario();
        $socio = $this->socio($local, ['numero_camiseta' => 7]);

        foreach ([0, 100, -3] as $malo) {
            $this->guardar($partido, $this->fila($socio, $local, ['numero' => $malo]))
                ->assertSessionHasErrors();
        }

        $this->assertSame(7, (int) $socio->fresh()->numero_camiseta, 'Quedo como estaba.');
    }

    public function test_un_numero_invalido_no_registra_el_partido(): void
    {
        [$partido, $local] = $this->escenario();
        $socio = $this->socio($local);

        $this->guardar($partido, $this->fila($socio, $local, ['numero' => 500, 'cantidades' => [IncidenciaPartido::TIPO_GOL => 2]]))
            ->assertSessionHasErrors();

        $this->assertSame(
            Partido::ESTADO_PROGRAMADO,
            $partido->fresh()->estado,
            'Si el formulario no pasa, no se registra nada a medias.',
        );
        $this->assertSame(0, $partido->incidencias()->count());
    }

    // ================= QUE NO ROMPA LO QUE YA HABIA =================

    public function test_sigue_registrando_goles_y_el_marcador(): void
    {
        [$partido, $local, $visita] = $this->escenario();
        $uno = $this->socio($local);
        $otro = $this->socio($visita);

        $this->guardar($partido, $this->planilla(
            $this->fila($uno, $local, ['numero' => 7, 'cantidades' => [IncidenciaPartido::TIPO_GOL => 2]]),
            $this->fila($otro, $visita, ['carnet' => '1', 'cantidades' => [IncidenciaPartido::TIPO_GOL => 1]]),
        ))->assertRedirect();

        $partido->refresh();
        $this->assertSame(2, $partido->goles_local);
        $this->assertSame(1, $partido->goles_visitante);
        $this->assertSame(Partido::ESTADO_JUGADO, $partido->estado);
        $this->assertSame(3, $partido->incidencias()->count());

        // Y ademas quedo lo de la planilla.
        $this->assertSame(7, (int) $uno->fresh()->numero_camiseta);
        $this->assertSame(Socio::CARNET_TIENE, $otro->fresh()->carnet);
    }

    public function test_el_aviso_dice_cuantas_fichas_se_actualizaron(): void
    {
        [$partido, $local] = $this->escenario();
        $uno = $this->socio($local);
        $dos = $this->socio($local);

        $this->guardar($partido, $this->planilla(
            $this->fila($uno, $local, ['numero' => 1]),
            $this->fila($dos, $local, ['numero' => 2]),
        ))->assertSessionHas('status', fn (string $aviso) => str_contains($aviso, '2 socios'));
    }

    public function test_sin_cambios_el_aviso_no_habla_de_fichas(): void
    {
        [$partido, $local] = $this->escenario();
        $socio = $this->socio($local);

        $this->guardar($partido, $this->fila($socio, $local))
            ->assertSessionHas('status', fn (string $aviso) => ! str_contains($aviso, 'Se actualizo'));
    }

    public function test_registrar_dos_veces_deja_el_ultimo_numero(): void
    {
        [$partido, $local] = $this->escenario();
        $socio = $this->socio($local);

        $this->guardar($partido, $this->fila($socio, $local, ['numero' => 4]));
        $this->assertSame(4, (int) $socio->fresh()->numero_camiseta);

        $this->guardar($partido, $this->fila($socio, $local, ['numero' => 14]));
        $this->assertSame(14, (int) $socio->fresh()->numero_camiseta);
    }

    // ================= PERMISOS =================

    public function test_el_directivo_no_puede_llegar_a_esta_pantalla(): void
    {
        [$partido, $local] = $this->escenario();
        $socio = $this->socio($local, ['numero_camiseta' => 2]);

        $directivo = User::where('role', User::ROLE_DIRECTIVO)->first();
        if (! $directivo) {
            $this->markTestSkipped('No hay un directivo en esta base.');
        }

        $this->actingAs($directivo)
            ->post(route('partidos.registro.guardar', $partido), [
                'jugadores' => $this->fila($socio, $local, ['numero' => 50, 'carnet' => '1']),
            ])
            ->assertRedirect(route('socios.index'));

        $socio->refresh();
        $this->assertSame(2, (int) $socio->numero_camiseta, 'No pudo escribir en la ficha.');
        $this->assertSame(Socio::CARNET_NO_TIENE, $socio->carnet);
    }
}
