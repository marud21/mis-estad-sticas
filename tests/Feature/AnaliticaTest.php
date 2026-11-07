<?php

namespace Tests\Feature;

use App\Models\User;
use Tests\TestCase;

/**
 * La medicion de visitas con Google Analytics.
 *
 * Lo que mas importa comprobar es lo que NO debe pasar: que sin
 * identificador configurado no se cargue nada de Google, y que el panel
 * de administracion nunca se mida.
 */
class AnaliticaTest extends TestCase
{
    private const ID = 'G-PRUEBA12345';

    private function conMedicion(): void
    {
        config(['analitica.google_id' => self::ID, 'analitica.avisar_cookies' => true]);
    }

    private function sinMedicion(): void
    {
        config(['analitica.google_id' => null]);
    }

    /** @return array<int, string> las paginas publicas que se miden */
    private static function paginasPublicas(): array
    {
        return [
            route('home'),
            route('noticias-publicas.index'),
            route('consulta.index'),
        ];
    }

    public function test_sin_identificador_no_se_carga_nada_de_google(): void
    {
        $this->sinMedicion();

        foreach (self::paginasPublicas() as $url) {
            $html = $this->get($url)->assertOk()->getContent();

            $this->assertStringNotContainsString('googletagmanager', $html, "Se filtro Google en {$url}");
            $this->assertStringNotContainsString('gtag(', $html);
            $this->assertStringNotContainsString('aviso-cookies', $html, 'Sin medicion no hay por que avisar de cookies.');
        }
    }

    public function test_con_identificador_se_carga_en_las_paginas_publicas(): void
    {
        $this->conMedicion();

        foreach (self::paginasPublicas() as $url) {
            $html = $this->get($url)->assertOk()->getContent();

            $this->assertStringContainsString('googletagmanager.com/gtag/js?id='.self::ID, $html, "Falta en {$url}");
            $this->assertStringContainsString('anonymize_ip', $html, 'La IP debe ir recortada.');
        }
    }

    public function test_el_panel_de_administracion_nunca_se_mide(): void
    {
        $this->conMedicion();

        $html = $this->actingAs(User::firstOrFail())
            ->get(route('panel.index'))
            ->assertOk()
            ->getContent();

        $this->assertStringNotContainsString('googletagmanager', $html, 'El uso propio ensuciaria las cifras.');
    }

    public function test_las_demas_pantallas_privadas_tampoco(): void
    {
        $this->conMedicion();

        foreach ([route('socios.index'), route('equipos.index'), route('partidos.index')] as $url) {
            $html = $this->actingAs(User::firstOrFail())->get($url)->assertOk()->getContent();

            $this->assertStringNotContainsString('googletagmanager', $html, "Se filtro Google en {$url}");
        }
    }

    public function test_el_aviso_de_cookies_sale_con_la_medicion(): void
    {
        $this->conMedicion();
        $html = $this->get(route('home'))->assertOk()->getContent();

        $this->assertStringContainsString('id="aviso-cookies"', $html);
        $this->assertStringContainsString('cookies para contar las visitas', $html);
    }

    public function test_el_aviso_se_puede_apagar_sin_apagar_la_medicion(): void
    {
        config(['analitica.google_id' => self::ID, 'analitica.avisar_cookies' => false]);

        $html = $this->get(route('home'))->assertOk()->getContent();

        $this->assertStringContainsString('googletagmanager', $html, 'La medicion sigue.');
        $this->assertStringNotContainsString('id="aviso-cookies"', $html);
    }

    public function test_el_identificador_se_escapa_en_la_pagina(): void
    {
        // Si alguien pone basura en el .env, no debe poder inyectar codigo.
        config(['analitica.google_id' => 'G-X</script><script>alert(1)</script>']);

        $html = $this->get(route('home'))->assertOk()->getContent();

        $this->assertStringNotContainsString('<script>alert(1)</script>', $html);
    }

    public function test_la_noticia_publicada_tambien_se_mide(): void
    {
        \Illuminate\Support\Facades\DB::beginTransaction();
        $this->conMedicion();

        $noticia = \App\Models\Noticia::create([
            'titulo' => 'Noticia de prueba '.uniqid(),
            'contenido' => 'Contenido de prueba.',
            'fecha_publicacion' => now()->toDateString(),
            'publicado' => true,
            'user_id' => User::firstOrFail()->id,
        ]);

        $html = $this->get(route('noticias-publicas.show', $noticia))->assertOk()->getContent();

        $this->assertStringContainsString('googletagmanager', $html);
        \Illuminate\Support\Facades\DB::rollBack();
    }
}
