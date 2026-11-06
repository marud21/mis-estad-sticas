<?php

namespace Tests\Feature;

use App\Models\Socio;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * El estado "suspendido" se sigue guardando asi, pero en pantalla la
 * corporacion lo llama "sostenimiento".
 */
class EtiquetaEstadoSocioTest extends TestCase
{
    private function socio(string $estado): Socio
    {
        return Socio::create([
            'nombre_completo' => 'P ETIQUETA '.uniqid(), 'numero_documento' => uniqid('e'),
            'fecha_nacimiento' => '1990-01-01', 'entidad_salud' => 'X', 'celular' => '300',
            'tipo_sangre' => 'O+', 'direccion_residencia' => 'X', 'posicion_juego' => 'Medio',
            'nivel_jugador' => 2, 'estado' => $estado,
        ]);
    }

    public function test_el_valor_guardado_no_cambia(): void
    {
        DB::beginTransaction();
        $socio = $this->socio(Socio::ESTADO_SUSPENDIDO);

        $this->assertEquals('suspendido', $socio->fresh()->estado, 'En la base se sigue llamando suspendido.');
        $this->assertEquals('suspendido', Socio::ESTADO_SUSPENDIDO);
        DB::rollBack();
    }

    public function test_en_pantalla_se_llama_sostenimiento(): void
    {
        DB::beginTransaction();
        $socio = $this->socio(Socio::ESTADO_SUSPENDIDO);

        $this->assertEquals('Sostenimiento', $socio->estado_etiqueta);
        DB::rollBack();
    }

    public function test_los_demas_estados_no_cambian_de_nombre(): void
    {
        DB::beginTransaction();
        $esperado = [
            Socio::ESTADO_ACTIVO => 'Activo',
            Socio::ESTADO_RETIRADO => 'Retirado',
            Socio::ESTADO_EXCLUIDO => 'Excluido',
        ];

        foreach ($esperado as $estado => $etiqueta) {
            $this->assertEquals($etiqueta, $this->socio($estado)->estado_etiqueta);
        }
        DB::rollBack();
    }

    public function test_la_ficha_del_socio_muestra_sostenimiento(): void
    {
        DB::beginTransaction();
        $socio = $this->socio(Socio::ESTADO_SUSPENDIDO);

        $html = $this->actingAs(User::firstOrFail())
            ->get(route('socios.show', $socio))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('Sostenimiento', $html);
        $this->assertStringNotContainsString('>Suspendido<', $html, 'No debe quedar la etiqueta vieja.');
        // El valor sigue viajando en el formulario, que es lo que se guarda.
        $this->assertStringContainsString('value="suspendido"', $html);
        DB::rollBack();
    }

    public function test_el_filtro_de_socios_ofrece_sostenimiento(): void
    {
        $html = $this->actingAs(User::firstOrFail())
            ->get(route('socios.index', ['estado' => Socio::ESTADO_SUSPENDIDO]))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('Sostenimiento', $html);
        $this->assertStringNotContainsString('>Suspendido<', $html);
    }

    public function test_el_estado_se_sigue_pudiendo_cambiar_por_su_valor(): void
    {
        DB::beginTransaction();
        $socio = $this->socio(Socio::ESTADO_ACTIVO);

        $this->actingAs(User::firstOrFail())
            ->patch(route('socios.estado', $socio), ['estado' => 'suspendido'])
            ->assertRedirect();

        $this->assertEquals('suspendido', $socio->fresh()->estado);
        $this->assertEquals('Sostenimiento', $socio->fresh()->estado_etiqueta);
        DB::rollBack();
    }
}
