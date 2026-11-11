<?php

namespace Tests\Feature;

use App\Models\Equipo;
use App\Models\Socio;
use App\Models\User;
use App\Support\Permisos;
use PHPUnit\Framework\Attributes\DataProvider;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * El rol directivo.
 *
 * Lo que mas importa comprobar no es lo que puede, sino lo que NO: que
 * una ruta cerrada siga cerrada aunque escriba la direccion a mano, no
 * solo que el menu no la ofrezca.
 */
class RolDirectivoTest extends TestCase
{
    private function directivo(): User
    {
        return User::create([
            'name' => 'Directivo de prueba',
            'email' => 'directivo'.uniqid().'@corvepatios.com',
            'password' => 'secreto-de-prueba',
            'role' => User::ROLE_DIRECTIVO,
        ]);
    }

    private function admin(): User
    {
        return User::where('role', User::ROLE_ADMIN)->firstOrFail();
    }

    private function socio(): Socio
    {
        return Socio::create([
            'nombre_completo' => 'R SOCIO '.uniqid(), 'numero_documento' => uniqid('r'),
            'fecha_nacimiento' => '1990-01-01', 'entidad_salud' => 'X', 'celular' => '300',
            'tipo_sangre' => 'O+', 'direccion_residencia' => 'X', 'posicion_juego' => 'Medio',
            'nivel_jugador' => 2, 'estado' => 'activo',
        ]);
    }

    // ---------- Lo que SI puede ----------

    public static function rutasPermitidas(): array
    {
        return [
            'tipos de cargo' => ['tipos-cargo.index'],
            'crear tipo de cargo' => ['tipos-cargo.create'],
            'cuota moderada' => ['cuota-moderada.index'],
            'cierre de caja' => ['cierre-caja.index'],
            'configuracion' => ['configuracion.index'],
            'cambiar contrasena' => ['password.edit'],
            'noticias' => ['noticias-admin.index'],
            'socios (lista)' => ['socios.index'],
            'equipos (lista)' => ['equipos.index'],
        ];
    }

    #[DataProvider('rutasPermitidas')]
    public function test_el_directivo_entra_a_lo_suyo(string $ruta): void
    {
        DB::beginTransaction();
        $this->actingAs($this->directivo())->get(route($ruta))->assertOk();
        DB::rollBack();
    }

    // ---------- Lo que NO puede ----------

    public static function rutasProhibidas(): array
    {
        return [
            'panel' => ['panel.index'],
            'reportes contables' => ['reportes-contables.index'],
            'torneos' => ['torneos.index'],
            'partidos' => ['partidos.index'],
            'tabla de posiciones' => ['partidos.posiciones'],
            'sanciones' => ['sanciones.index'],
            'planilla de juego' => ['planilla-juego.index'],
            'crear socio' => ['socios.create'],
            'crear equipo' => ['equipos.create'],
            'importar datos' => ['importar.index'],
        ];
    }

    /**
     * La carga masiva crea o cambia cargos de cientos de socios de un
     * golpe. Aunque vive dentro de Finanzas, se le cierra: es el mismo
     * motivo por el que no puede editar un socio.
     */
    public function test_el_directivo_no_puede_hacer_cargas_masivas(): void
    {
        DB::beginTransaction();
        $directivo = $this->directivo();
        $tipo = \App\Models\TipoCargo::firstOrCreate(
            ['nombre' => 'Mensualidad'],
            ['monto_default' => 10000, 'es_recurrente' => true, 'porcentaje_suspendido' => 50],
        );
        $cargosAntes = \App\Models\Cargo::count();

        $this->actingAs($directivo)
            ->post(route('tipos-cargo.aplicar-masivo', $tipo), ['nivel' => 'todos', 'fecha' => now()->toDateString()])
            ->assertRedirect(route('socios.index'));

        $this->actingAs($directivo)
            ->post(route('tipos-cargo.modificar-masivo', $tipo), ['nivel' => 'todos', 'monto' => 1])
            ->assertRedirect(route('socios.index'));

        $this->assertEquals($cargosAntes, \App\Models\Cargo::count(), 'No se creo ni se cambio ningun cargo.');
        DB::rollBack();
    }

    /**
     * Recalcular reescribe la cuota de todos los socios con deuda. Es la
     * misma familia que la carga masiva, asi que tambien se le cierra.
     * Ajustar el porcentaje si puede: eso es solo un ajuste y no toca a
     * nadie hasta que alguien recalcule.
     */
    public function test_el_directivo_no_puede_recalcular_la_cuota_moderada(): void
    {
        DB::beginTransaction();
        $directivo = $this->directivo();
        $cuotasAntes = Socio::whereNotNull('cuota_moderada')->count();

        $this->actingAs($directivo)
            ->post(route('cuota-moderada.recalcular'))
            ->assertRedirect(route('socios.index'));

        $this->assertEquals(
            $cuotasAntes,
            Socio::whereNotNull('cuota_moderada')->count(),
            'No se recalculo la cuota de nadie.',
        );
        DB::rollBack();
    }

    public function test_el_directivo_si_puede_ajustar_el_porcentaje(): void
    {
        DB::beginTransaction();

        $this->actingAs($this->directivo())
            ->put(route('cuota-moderada.porcentaje'), ['porcentaje' => 30])
            ->assertRedirect(route('cuota-moderada.index'));

        DB::rollBack();
    }

    public function test_no_se_le_muestra_el_boton_de_recalcular(): void
    {
        DB::beginTransaction();
        $html = $this->actingAs($this->directivo())->get(route('cuota-moderada.index'))->assertOk()->getContent();
        $this->assertStringNotContainsString('cuota-moderada/recalcular', $html);

        $htmlAdmin = $this->actingAs($this->admin())->get(route('cuota-moderada.index'))->assertOk()->getContent();
        $this->assertStringContainsString('cuota-moderada/recalcular', $htmlAdmin, 'Al admin si.');
        DB::rollBack();
    }

    public function test_el_directivo_no_puede_borrar_cargos_duplicados(): void
    {
        DB::beginTransaction();
        $cargosAntes = \App\Models\Cargo::count();

        $this->actingAs($this->directivo())
            ->post(route('cargos-duplicados.eliminar'))
            ->assertRedirect(route('socios.index'));

        $this->assertEquals($cargosAntes, \App\Models\Cargo::count(), 'No se borro ningun cargo.');
        DB::rollBack();
    }

    /**
     * Una ruta privada sin nombre no debe dejar al administrador fuera de
     * su propio sistema. Hoy todas tienen nombre, pero el dia que alguien
     * agregue una sin nombrar, el admin debe seguir pasando.
     */
    public function test_una_ruta_sin_nombre_no_encierra_al_administrador(): void
    {
        $this->assertTrue(Permisos::puede($this->admin(), null));
        $this->assertTrue(Permisos::puede($this->admin(), ''));
    }

    public function test_el_administrador_conserva_todas_las_rutas_privadas(): void
    {
        $admin = $this->admin();
        $cerradas = [];

        foreach (\Illuminate\Support\Facades\Route::getRoutes() as $ruta) {
            if (! str_contains(implode(' ', $ruta->gatherMiddleware()), 'auth')) {
                continue;
            }

            if (! Permisos::puede($admin, $ruta->getName())) {
                $cerradas[] = $ruta->getName() ?: $ruta->uri();
            }
        }

        $this->assertSame([], $cerradas, 'Al administrador se le cerraron rutas: '.implode(', ', $cerradas));
    }

    public function test_el_directivo_no_puede_importar_datos(): void
    {
        DB::beginTransaction();
        $directivo = $this->directivo();
        $sociosAntes = Socio::count();

        $this->actingAs($directivo)->get(route('importar.index'))->assertRedirect(route('socios.index'));
        $this->actingAs($directivo)->post(route('importar.socios'))->assertRedirect(route('socios.index'));

        $this->assertEquals($sociosAntes, Socio::count(), 'No entro ningun socio.');
        DB::rollBack();
    }

    public function test_no_se_le_muestran_los_botones_de_carga_masiva(): void
    {
        DB::beginTransaction();
        $html = $this->actingAs($this->directivo())->get(route('tipos-cargo.index'))->assertOk()->getContent();

        $this->assertStringNotContainsString('aplicar-masivo', $html);
        $this->assertStringNotContainsString('modificar-masivo', $html);
        DB::rollBack();
    }

    public function test_al_admin_si_se_le_muestran(): void
    {
        $html = $this->actingAs($this->admin())->get(route('tipos-cargo.index'))->assertOk()->getContent();

        $this->assertStringContainsString('aplicar-masivo', $html);
    }

    #[DataProvider('rutasProhibidas')]
    public function test_el_directivo_rebota_aunque_escriba_la_direccion(string $ruta): void
    {
        DB::beginTransaction();

        $this->actingAs($this->directivo())
            ->get(route($ruta))
            ->assertRedirect(route('socios.index'))
            ->assertSessionHas('status');

        DB::rollBack();
    }

    public function test_el_directivo_no_puede_modificar_un_socio(): void
    {
        DB::beginTransaction();
        $socio = $this->socio();
        $directivo = $this->directivo();

        $this->actingAs($directivo)->get(route('socios.edit', $socio))->assertRedirect();
        $this->actingAs($directivo)->patch(route('socios.estado', $socio), ['estado' => 'retirado'])->assertRedirect(route('socios.index'));
        $this->actingAs($directivo)->delete(route('socios.destroy', $socio))->assertRedirect(route('socios.index'));

        $this->assertNotNull($socio->fresh(), 'El socio sigue existiendo.');
        $this->assertEquals('activo', $socio->fresh()->estado, 'Su estado no cambio.');
        DB::rollBack();
    }

    public function test_el_directivo_no_puede_modificar_un_equipo(): void
    {
        DB::beginTransaction();
        $equipo = Equipo::create(['nombre' => 'R EQUIPO '.uniqid(), 'estado' => 'activo']);
        $directivo = $this->directivo();

        $this->actingAs($directivo)->get(route('equipos.edit', $equipo))->assertRedirect();
        $this->actingAs($directivo)->delete(route('equipos.destroy', $equipo))->assertRedirect();

        $this->assertNotNull($equipo->fresh());
        DB::rollBack();
    }

    public function test_el_directivo_si_puede_ver_la_ficha_de_un_socio(): void
    {
        DB::beginTransaction();
        $socio = $this->socio();

        $html = $this->actingAs($this->directivo())
            ->get(route('socios.show', $socio))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString($socio->nombre_completo, $html);
        DB::rollBack();
    }

    // ---------- El menu ----------

    public function test_el_menu_del_directivo_no_ofrece_lo_que_no_puede(): void
    {
        DB::beginTransaction();
        $html = $this->actingAs($this->directivo())->get(route('socios.index'))->assertOk()->getContent();

        foreach (['Competencia', 'Reportes contables', '>Panel<', 'Importar datos'] as $texto) {
            $this->assertStringNotContainsString($texto, $html, "El menu ofrece '{$texto}' y no deberia.");
        }

        foreach (['Finanzas', 'Sistema', 'Noticias', 'Socios', 'Equipos'] as $texto) {
            $this->assertStringContainsString($texto, $html, "Falta '{$texto}' en el menu.");
        }
        DB::rollBack();
    }

    public function test_el_menu_del_admin_sigue_completo(): void
    {
        $html = $this->actingAs($this->admin())->get(route('panel.index'))->assertOk()->getContent();

        foreach (['Competencia', 'Finanzas', 'Sistema', 'Panel', 'Socios', 'Equipos', 'Noticias'] as $texto) {
            $this->assertStringContainsString($texto, $html);
        }
    }

    public function test_al_directivo_se_le_esconden_los_botones_de_modificar(): void
    {
        DB::beginTransaction();
        $this->socio();
        $html = $this->actingAs($this->directivo())->get(route('socios.index'))->assertOk()->getContent();

        $this->assertStringNotContainsString('Nuevo socio', $html);
        $this->assertStringNotContainsString('>Editar<', $html);
        $this->assertStringNotContainsString('>Eliminar<', $html);
        DB::rollBack();
    }

    public function test_al_admin_no_se_le_esconde_nada(): void
    {
        $html = $this->actingAs($this->admin())->get(route('socios.index'))->assertOk()->getContent();

        $this->assertStringContainsString('Nuevo socio', $html);
    }

    // ---------- Al entrar ----------

    public function test_el_directivo_entra_a_socios_y_el_admin_al_panel(): void
    {
        DB::beginTransaction();
        $this->assertEquals(route('socios.index'), $this->directivo()->pantallaDeInicio());
        $this->assertEquals(route('panel.index'), $this->admin()->pantallaDeInicio());
        DB::rollBack();
    }

    // ---------- La regla de fondo ----------

    public function test_una_ruta_nueva_nace_cerrada_para_el_directivo(): void
    {
        DB::beginTransaction();
        $directivo = $this->directivo();

        $this->assertFalse(
            Permisos::puede($directivo, 'modulo-que-no-existe.index'),
            'Una pantalla nueva debe quedar cerrada hasta que alguien la abra a proposito.',
        );
        $this->assertTrue(Permisos::puede($this->admin(), 'modulo-que-no-existe.index'), 'El admin puede todo.');
        DB::rollBack();
    }

    public function test_un_rol_desconocido_no_se_trata_como_admin(): void
    {
        DB::beginTransaction();
        $raro = User::create([
            'name' => 'Rol raro', 'email' => 'raro'.uniqid().'@corvepatios.com',
            'password' => 'secreto-de-prueba', 'role' => 'inventado',
        ]);

        $this->assertFalse(Permisos::puede($raro, 'socios.index'));
        $this->assertTrue(Permisos::puede($raro, 'logout'), 'Siempre debe poder salir.');
        DB::rollBack();
    }

    public function test_sin_sesion_no_se_puede_nada(): void
    {
        $this->assertFalse(Permisos::puede(null, 'socios.index'));
    }
}
