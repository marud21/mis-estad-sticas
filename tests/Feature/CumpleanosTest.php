<?php

namespace Tests\Feature;

use App\Models\Equipo;
use App\Models\Socio;
use App\Models\Torneo;
use App\Services\CumpleanosService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class CumpleanosTest extends TestCase
{
    private function socio(string $nombre, string $nacimiento, string $estado = Socio::ESTADO_ACTIVO, ?Equipo $equipo = null): Socio
    {
        $s = Socio::create([
            'nombre_completo' => $nombre, 'numero_documento' => uniqid('c'),
            'fecha_nacimiento' => $nacimiento, 'entidad_salud' => 'X', 'celular' => '300',
            'tipo_sangre' => 'O+', 'direccion_residencia' => 'X', 'posicion_juego' => 'Medio',
            'nivel_jugador' => 2, 'estado' => $estado,
        ]);

        if ($equipo) {
            $s->equipos()->attach($equipo->id);
        }

        return $s;
    }

    private function equipo(): Equipo
    {
        $t = Torneo::create(['nombre' => 'Torneo cumple '.uniqid(), 'fecha_inicio' => '2030-01-01']);

        return Equipo::create(['nombre' => 'EQ CUMPLE '.uniqid(), 'torneo_id' => $t->id, 'estado' => 'activo']);
    }

    private function servicio(): CumpleanosService
    {
        return app(CumpleanosService::class);
    }

    public function test_encuentra_a_quien_cumple_hoy_sin_importar_el_año(): void
    {
        DB::beginTransaction();
        $hoy = Carbon::create(2026, 7, 15);
        $cumple = $this->socio('C DE HOY', '1980-07-15');
        $otro = $this->socio('C DE OTRO DIA', '1980-07-16');

        $lista = $this->servicio()->deHoy($hoy)->pluck('nombre_completo');

        $this->assertContains('C DE HOY', $lista->all(), 'Nacio un 15 de julio, hoy es 15 de julio.');
        $this->assertNotContains('C DE OTRO DIA', $lista->all());
        DB::rollBack();
    }

    public function test_no_confunde_el_mismo_dia_de_otro_mes(): void
    {
        DB::beginTransaction();
        $hoy = Carbon::create(2026, 7, 15);
        $this->socio('C MISMO DIA OTRO MES', '1980-03-15');

        $lista = $this->servicio()->deHoy($hoy)->pluck('nombre_completo');

        $this->assertNotContains('C MISMO DIA OTRO MES', $lista->all());
        DB::rollBack();
    }

    public function test_los_retirados_no_se_saludan(): void
    {
        DB::beginTransaction();
        $hoy = Carbon::create(2026, 7, 15);
        $this->socio('C RETIRADO', '1980-07-15', Socio::ESTADO_RETIRADO);

        $lista = $this->servicio()->deHoy($hoy)->pluck('nombre_completo');

        $this->assertNotContains('C RETIRADO', $lista->all(), 'Un retirado ya no pertenece al club.');
        DB::rollBack();
    }

    public function test_los_de_sostenimiento_y_excluidos_si_se_saludan(): void
    {
        DB::beginTransaction();
        $hoy = Carbon::create(2026, 7, 15);
        $this->socio('C SOSTENIMIENTO', '1980-07-15', Socio::ESTADO_SUSPENDIDO);
        $this->socio('C EXCLUIDO', '1980-07-15', Socio::ESTADO_EXCLUIDO);

        $lista = $this->servicio()->deHoy($hoy)->pluck('nombre_completo');

        $this->assertContains('C SOSTENIMIENTO', $lista->all(), 'Es una situacion de pagos, sigue siendo socio.');
        $this->assertContains('C EXCLUIDO', $lista->all());
        DB::rollBack();
    }

    public function test_al_29_de_febrero_se_le_saluda_el_28_en_años_normales(): void
    {
        DB::beginTransaction();
        $this->socio('C BISIESTO', '1980-02-29');

        // 2027 no es bisiesto: se le saluda el 28.
        $lista = $this->servicio()->deHoy(Carbon::create(2027, 2, 28))->pluck('nombre_completo');
        $this->assertContains('C BISIESTO', $lista->all(), 'Si no, no cumpliria años casi nunca.');

        // 2028 si es bisiesto: el 28 todavia no le toca.
        $lista = $this->servicio()->deHoy(Carbon::create(2028, 2, 28))->pluck('nombre_completo');
        $this->assertNotContains('C BISIESTO', $lista->all());

        // Y el 29 de ese año bisiesto si.
        $lista = $this->servicio()->deHoy(Carbon::create(2028, 2, 29))->pluck('nombre_completo');
        $this->assertContains('C BISIESTO', $lista->all());
        DB::rollBack();
    }

    public function test_trae_el_equipo_del_socio(): void
    {
        DB::beginTransaction();
        $hoy = Carbon::create(2026, 7, 15);
        $equipo = $this->equipo();
        $this->socio('C CON EQUIPO', '1980-07-15', Socio::ESTADO_ACTIVO, $equipo);

        $encontrado = $this->servicio()->deHoy($hoy)->firstWhere('nombre_completo', 'C CON EQUIPO');

        $this->assertEquals([$equipo->nombre], $encontrado->equipos->pluck('nombre')->all());
        DB::rollBack();
    }

    // ---------- La portada ----------

    public function test_la_portada_saluda_al_cumpleañero(): void
    {
        DB::beginTransaction();
        $equipo = $this->equipo();
        $this->socio('CUMPLEANERO DE PRUEBA', now()->format('1985-m-d'), Socio::ESTADO_ACTIVO, $equipo);

        $html = $this->get(route('home'))->assertOk()->getContent();

        $this->assertStringContainsString('Feliz Cumpleaños', $html);
        $this->assertStringContainsString('CUMPLEANERO DE PRUEBA', $html);
        $this->assertStringContainsString($equipo->nombre, $html, 'Debe verse el equipo.');
        $this->assertStringContainsString(
            'class="franja-superior con-cumple"',
            $html,
            'Con saludo, el encabezado le cede la columna de la derecha.'
        );
        DB::rollBack();
    }

    /**
     * El caso "hoy no cumple nadie" se comprueba renderizando la vista con
     * la lista vacia, no corriendo los cumpleaños de los socios reales:
     * eso bloquearia la tabla entera para las demas pruebas.
     */
    public function test_sin_cumpleañeros_no_se_dibuja_la_tarjeta(): void
    {
        $html = $this->portadaCon(collect());

        $this->assertStringNotContainsString('Feliz Cumpleaños', $html);
        // Se mira el atributo class, no la cadena suelta: "con-cumple"
        // tambien aparece en la hoja de estilos de la pagina.
        $this->assertStringNotContainsString(
            'class="franja-superior con-cumple"',
            $html,
            'Sin saludo, el encabezado ocupa todo el ancho.'
        );
    }

    public function test_el_socio_sin_equipo_tambien_aparece(): void
    {
        DB::beginTransaction();
        $this->socio('CUMPLE SIN EQUIPO', now()->format('1985-m-d'));

        $html = $this->get(route('home'))->assertOk()->getContent();

        $this->assertStringContainsString('CUMPLE SIN EQUIPO', $html);
        $this->assertStringContainsString('Sin equipo', $html);
        DB::rollBack();
    }

    public function test_el_saludo_no_depende_de_que_haya_torneo(): void
    {
        DB::beginTransaction();
        $cumpleanero = $this->socio('CUMPLE SIN TORNEO', now()->format('1985-m-d'));

        $html = $this->portadaCon(collect([$cumpleanero->load('equipos')]));

        $this->assertStringContainsString('Todavia no hay torneos publicados', $html);
        $this->assertStringContainsString('CUMPLE SIN TORNEO', $html);
        DB::rollBack();
    }

    // ---------- El carrusel ----------

    public function test_con_un_solo_cumpleañero_no_hay_carrusel(): void
    {
        DB::beginTransaction();
        $uno = $this->socio('CUMPLE UNICO', now()->format('1985-m-d'));

        $html = $this->portadaCon(collect([$uno->load('equipos')]));

        $this->assertStringNotContainsString('data-carrusel', $html, 'Con uno solo no hay nada que rotar.');
        // Se mira el atributo, no la cadena suelta: el nombre de la clase
        // tambien esta en la hoja de estilos.
        $this->assertStringNotContainsString('class="cumple-punto"', $html, 'Ni puntos de navegacion.');
        $this->assertStringContainsString('Que lo disfrutes', $html, 'El mensaje va en singular.');
        DB::rollBack();
    }

    public function test_con_varios_se_arma_el_carrusel(): void
    {
        DB::beginTransaction();
        $a = $this->socio('CUMPLE UNO', now()->format('1985-m-d'));
        $b = $this->socio('CUMPLE DOS', now()->format('1985-m-d'));
        $c = $this->socio('CUMPLE TRES', now()->format('1985-m-d'));

        $html = $this->portadaCon(collect([$a, $b, $c])->each->load('equipos'));

        $this->assertStringContainsString('data-carrusel', $html);
        // Un punto por persona.
        $this->assertEquals(3, substr_count($html, 'class="cumple-punto"'));
        $this->assertStringContainsString('Que lo disfruten', $html, 'El mensaje va en plural.');
        DB::rollBack();
    }

    public function test_el_html_trae_a_todos_aunque_se_vea_uno_a_la_vez(): void
    {
        DB::beginTransaction();
        $a = $this->socio('CUMPLE VISIBLE', now()->format('1985-m-d'));
        $b = $this->socio('CUMPLE ESCONDIDO', now()->format('1985-m-d'));

        $html = $this->portadaCon(collect([$a, $b])->each->load('equipos'));

        // Si el guion no corre, se ven los dos: nadie queda fuera del saludo.
        $this->assertStringContainsString('CUMPLE VISIBLE', $html);
        $this->assertStringContainsString('CUMPLE ESCONDIDO', $html);
        $this->assertStringNotContainsString(
            'cumple-lista carrusel',
            $html,
            'La clase que esconde las fichas la pone el guion, no el servidor.'
        );
        DB::rollBack();
    }

    /** Renderiza la portada sin torneos, con la lista de cumpleañeros dada. */
    private function portadaCon(\Illuminate\Support\Collection $cumpleaneros): string
    {
        return view('home', [
            'torneos' => collect(),
            'torneo' => null,
            'cumpleaneros' => $cumpleaneros,
            'carrusel' => collect(),
            'programacionPorCategoria' => collect(),
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
    }
}
