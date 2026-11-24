<?php

namespace Tests\Feature;

use App\Models\Noticia;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * La ventana con la ultima noticia que salta en la portada.
 *
 * Lo que mas se cuida: que salte solo cuando corresponde. Una ventana
 * que interrumpe tiene que ganarse el derecho a hacerlo, asi que no sale
 * si la noticia no esta publicada, si todavia no le llego la fecha, o si
 * ya es vieja.
 *
 * Cada prueba corre dentro de una transaccion que se deshace al final,
 * porque la base de este equipo tiene los datos reales del club. Va en
 * setUp/tearDown para que tambien se deshaga si una prueba falla a la
 * mitad.
 */
class AvisoNoticiaPortadaTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        DB::beginTransaction();

        /*
         * Se parte de la tabla vacia, dentro de la transaccion que se
         * deshace al terminar. Si no, el dia que el club tenga noticias
         * publicadas, cualquier prueba que comprueba que la ventana NO
         * sale fallaria por una noticia real que no tiene nada que ver.
         */
        Noticia::query()->delete();
    }

    protected function tearDown(): void
    {
        DB::rollBack();
        parent::tearDown();
    }

    private function admin(): \App\Models\User
    {
        return \App\Models\User::where('role', \App\Models\User::ROLE_ADMIN)->firstOrFail();
    }

    private function noticia(array $extra = []): Noticia
    {
        return Noticia::create(array_merge([
            'titulo' => 'NOTICIA DE PRUEBA '.uniqid(),
            'contenido' => 'Contenido de la noticia de prueba para la ventana de la portada.',
            'fecha_publicacion' => Carbon::today()->toDateString(),
            'publicado' => true,
        ], $extra));
    }

    private function portada(): string
    {
        return $this->get(route('home'))->assertOk()->getContent();
    }

    /** Si la ventana esta en la pagina, para esa noticia. */
    private function saltaPara(Noticia $noticia): bool
    {
        $html = $this->portada();

        return str_contains($html, 'id="aviso-noticia"')
            && str_contains($html, 'data-noticia="'.$noticia->id.'"');
    }

    // ================= CUANDO SI SALE =================

    public function test_salta_con_la_ultima_noticia_publicada(): void
    {
        $noticia = $this->noticia();

        $html = $this->portada();

        $this->assertStringContainsString('id="aviso-noticia"', $html);
        $this->assertStringContainsString('data-noticia="'.$noticia->id.'"', $html);
        $this->assertStringContainsString($noticia->titulo, $html);
        $this->assertStringContainsString('Leer la noticia', $html);
    }

    public function test_enlaza_a_la_noticia_completa(): void
    {
        $noticia = $this->noticia();

        $this->assertStringContainsString(
            route('noticias-publicas.show', $noticia),
            $this->portada(),
        );
    }

    public function test_entre_varias_manda_la_mas_reciente(): void
    {
        $this->noticia(['fecha_publicacion' => Carbon::today()->subDays(5)->toDateString()]);
        $nueva = $this->noticia(['fecha_publicacion' => Carbon::today()->subDay()->toDateString()]);

        $this->assertTrue($this->saltaPara($nueva));
    }

    public function test_si_dos_son_del_mismo_dia_manda_la_ultima_creada(): void
    {
        $hoy = Carbon::today()->toDateString();
        $this->noticia(['fecha_publicacion' => $hoy, 'titulo' => 'LA PRIMERA '.uniqid()]);
        $segunda = $this->noticia(['fecha_publicacion' => $hoy, 'titulo' => 'LA SEGUNDA '.uniqid()]);

        $this->assertTrue(
            $this->saltaPara($segunda),
            'Mismo criterio que la lista publica: fecha y despues id.',
        );
    }

    public function test_nace_escondida_para_que_no_parpadee(): void
    {
        $this->noticia();

        $this->assertMatchesRegularExpression(
            '#<div class="aviso-noticia"[^>]*\shidden#',
            $this->portada(),
            'Si naciera visible, el que ya la cerro alcanzaria a verla un instante.',
        );
    }

    /*
     * Se busca la etiqueta <img>, no el nombre de la clase: ese nombre
     * tambien esta en la hoja de estilos, que viaja siempre, asi que
     * buscarlo solo daria por buena cualquier respuesta.
     */
    private const ETIQUETA_IMAGEN = '#<img class="aviso-noticia-imagen"#';

    public function test_muestra_la_imagen_cuando_la_noticia_tiene(): void
    {
        $this->noticia(['imagen_path' => 'noticias/ejemplo.jpg']);

        $html = $this->portada();
        $this->assertMatchesRegularExpression(self::ETIQUETA_IMAGEN, $html);
        $this->assertStringContainsString('noticias/ejemplo.jpg', $html);
    }

    public function test_sin_imagen_no_deja_el_hueco(): void
    {
        $this->noticia(['imagen_path' => null]);

        $html = $this->portada();
        $this->assertDoesNotMatchRegularExpression(self::ETIQUETA_IMAGEN, $html);
        $this->assertStringNotContainsString('aviso-noticia-marco"', $html);
    }

    public function test_la_imagen_se_muestra_entera_y_no_recortada(): void
    {
        $this->noticia(['imagen_path' => 'noticias/ejemplo.jpg']);
        $html = $this->portada();

        // Se mira la regla de la imagen en la hoja de estilos. "cover"
        // recorta para rellenar el recuadro, y en un afiche eso se come
        // el encabezado y el pie, que es donde dice de que se trata.
        preg_match('#\.aviso-noticia-imagen\s*\{(.*?)\}#s', $html, $m);
        $regla = $m[1] ?? '';

        $this->assertNotSame('', $regla, 'No se encontro la regla de la imagen.');
        $this->assertStringNotContainsString('cover', $regla, 'No debe recortar.');
        $this->assertStringContainsString('max-height', $regla, 'Pero si tiene un tope de alto.');
        $this->assertStringContainsString('height: auto', $regla, 'Para que no se deforme.');
    }

    public function test_la_imagen_lleva_a_la_noticia(): void
    {
        $noticia = $this->noticia(['imagen_path' => 'noticias/ejemplo.jpg']);

        $this->assertMatchesRegularExpression(
            '#<a class="aviso-noticia-marco" href="'.preg_quote(route('noticias-publicas.show', $noticia), '#').'"#',
            $this->portada(),
        );
    }

    public function test_con_imagen_el_resumen_va_mas_corto(): void
    {
        $largo = str_repeat('palabra ', 80);

        $sinImagen = $this->noticia(['contenido' => $largo, 'imagen_path' => null]);
        $htmlSin = $this->portada();
        $sinImagen->update(['publicado' => false]);

        $this->noticia(['contenido' => $largo, 'imagen_path' => 'noticias/ejemplo.jpg']);
        $htmlCon = $this->portada();

        $medir = function (string $html): int {
            // Se recorta primero al cuerpo de la ventana. Buscar "<p>" en
            // toda la pagina engancha parrafos de la portada que no
            // tienen nada que ver y da medidas sin sentido.
            $cuerpo = strstr($html, 'aviso-noticia-cuerpo');
            preg_match('#<p>(.*?)</p>#s', (string) $cuerpo, $m);

            return strlen(trim($m[1] ?? ''));
        };

        $this->assertGreaterThan(0, $medir($htmlSin));
        $this->assertLessThan(
            $medir($htmlSin),
            $medir($htmlCon),
            'Con afiche el texto va mas corto: la imagen ya cuenta de que se trata.',
        );
    }

    // ================= CUANDO NO SALE =================

    public function test_no_sale_si_no_hay_ninguna_noticia(): void
    {
        Noticia::query()->delete();

        $this->assertStringNotContainsString('id="aviso-noticia"', $this->portada());
    }

    public function test_no_sale_con_una_noticia_sin_publicar(): void
    {
        $this->noticia(['publicado' => false]);

        $this->assertStringNotContainsString('id="aviso-noticia"', $this->portada());
    }

    public function test_no_sale_con_una_noticia_vieja(): void
    {
        $this->noticia([
            'fecha_publicacion' => Carbon::today()->subDays(Noticia::DIAS_DESTACADA + 1)->toDateString(),
        ]);

        $this->assertStringNotContainsString(
            'id="aviso-noticia"',
            $this->portada(),
            'Pasado el plazo deja de interrumpir, aunque siga siendo la ultima.',
        );
    }

    public function test_el_ultimo_dia_del_plazo_todavia_sale(): void
    {
        $noticia = $this->noticia([
            'fecha_publicacion' => Carbon::today()->subDays(Noticia::DIAS_DESTACADA)->toDateString(),
        ]);

        $this->assertTrue($this->saltaPara($noticia));
    }

    public function test_no_sale_con_una_noticia_de_fecha_futura(): void
    {
        $this->noticia(['fecha_publicacion' => Carbon::today()->addDay()->toDateString()]);

        $this->assertStringNotContainsString(
            'id="aviso-noticia"',
            $this->portada(),
            'Un año mal digitado la dejaria saltando para siempre.',
        );
    }

    public function test_una_vieja_no_tapa_a_una_nueva_sin_publicar(): void
    {
        // La reciente esta sin publicar y la vieja ya paso su plazo: no
        // debe salir ninguna de las dos.
        $this->noticia(['publicado' => false]);
        $this->noticia([
            'publicado' => true,
            'fecha_publicacion' => Carbon::today()->subDays(Noticia::DIAS_DESTACADA + 3)->toDateString(),
        ]);

        $this->assertStringNotContainsString('id="aviso-noticia"', $this->portada());
    }

    // ================= DONDE SALE Y DONDE NO =================

    public function test_solo_salta_en_la_portada(): void
    {
        $this->noticia();

        foreach ([
            'noticias publicas' => route('noticias-publicas.index'),
            'consulta de deuda' => route('consulta.index'),
            'ingreso' => route('login'),
        ] as $etiqueta => $url) {
            $this->assertStringNotContainsString(
                'id="aviso-noticia"',
                $this->get($url)->assertOk()->getContent(),
                "No debe saltar en: $etiqueta",
            );
        }
    }

    // ================= QUE NO ROMPA LA PORTADA =================

    public function test_la_portada_sigue_funcionando_sin_noticias(): void
    {
        Noticia::query()->delete();

        $html = $this->get(route('home'))->assertOk()->getContent();

        foreach (['Fatal error', 'Undefined variable', 'SQLSTATE', 'Whoops'] as $malo) {
            $this->assertStringNotContainsString($malo, $html);
        }
    }

    public function test_la_portada_no_se_rompe_con_la_ventana(): void
    {
        $this->noticia();

        $html = $this->get(route('home'))->assertOk()->getContent();

        foreach (['Fatal error', 'Undefined variable', 'SQLSTATE', 'Whoops'] as $malo) {
            $this->assertStringNotContainsString($malo, $html);
        }
        // Y lo que ya habia sigue ahi.
        $this->assertStringContainsString('nav-publico', $html);
    }

    public function test_el_modelo_elige_bien_la_destacada(): void
    {
        Noticia::query()->delete();
        $this->assertNull(Noticia::paraLaVentana(), 'Sin noticias no salta nada.');

        $vieja = $this->noticia(['fecha_publicacion' => Carbon::today()->subDays(60)->toDateString()]);
        $this->assertNull(Noticia::paraLaVentana(), 'Una vieja no salta.');

        $nueva = $this->noticia(['fecha_publicacion' => Carbon::today()->toDateString()]);
        $this->assertSame($nueva->id, Noticia::paraLaVentana()?->id);

        $nueva->update(['publicado' => false]);
        $this->assertNull(Noticia::paraLaVentana(), 'Despublicarla la quita de la ventana.');
    }

    public function test_despublicar_la_noticia_quita_la_ventana(): void
    {
        $noticia = $this->noticia();
        $this->assertTrue($this->saltaPara($noticia));

        $noticia->update(['publicado' => false]);

        $this->assertStringNotContainsString('id="aviso-noticia"', $this->portada());
    }

    // ================= ELEGIR CUAL SALE =================

    public function test_una_noticia_sin_marcar_no_salta(): void
    {
        $this->noticia(['destacada' => false]);

        $this->assertStringNotContainsString(
            'id="aviso-noticia"',
            $this->portada(),
            'Publicarla no basta: hay que elegir que interrumpa.',
        );
    }

    public function test_salta_la_marcada_aunque_no_sea_la_mas_reciente(): void
    {
        $marcada = $this->noticia([
            'fecha_publicacion' => Carbon::today()->subDays(3)->toDateString(),
            'destacada' => true,
        ]);
        $this->noticia([
            'fecha_publicacion' => Carbon::today()->toDateString(),
            'destacada' => false,
        ]);

        $this->assertTrue(
            $this->saltaPara($marcada),
            'La mas nueva esta sin marcar, asi que manda la marcada.',
        );
    }

    public function test_entre_varias_marcadas_manda_la_mas_reciente(): void
    {
        $this->noticia(['fecha_publicacion' => Carbon::today()->subDays(4)->toDateString(), 'destacada' => true]);
        $nueva = $this->noticia(['fecha_publicacion' => Carbon::today()->subDay()->toDateString(), 'destacada' => true]);

        $this->assertTrue($this->saltaPara($nueva));
    }

    public function test_desmarcarla_la_quita_de_la_ventana(): void
    {
        $noticia = $this->noticia(['destacada' => true]);
        $this->assertTrue($this->saltaPara($noticia));

        $noticia->update(['destacada' => false]);

        $this->assertStringNotContainsString('id="aviso-noticia"', $this->portada());
    }

    public function test_una_marcada_vieja_sigue_sin_saltar(): void
    {
        $this->noticia([
            'destacada' => true,
            'fecha_publicacion' => Carbon::today()->subDays(Noticia::DIAS_DESTACADA + 1)->toDateString(),
        ]);

        $this->assertStringNotContainsString(
            'id="aviso-noticia"',
            $this->portada(),
            'Marcarla no la libra del plazo: es la red de seguridad si se olvida desmarcarla.',
        );
    }

    public function test_una_marcada_sin_publicar_no_salta(): void
    {
        $this->noticia(['destacada' => true, 'publicado' => false]);

        $this->assertStringNotContainsString('id="aviso-noticia"', $this->portada());
    }

    // ================= EL FORMULARIO DEL ADMINISTRADOR =================

    public function test_una_noticia_nueva_viene_marcada(): void
    {
        $html = $this->actingAs($this->admin())
            ->get(route('noticias-admin.create'))->assertOk()->getContent();

        $this->assertStringContainsString('name="destacada"', $html);
        $this->assertMatchesRegularExpression(
            '#<option value="1"[^>]*selected[^>]*>\s*Si: salta#',
            $html,
            'Lo normal es que lo que se publica se quiera avisar.',
        );
    }

    public function test_el_formulario_respeta_lo_que_ya_tiene_la_noticia(): void
    {
        $noticia = $this->noticia(['destacada' => false]);

        $html = $this->actingAs($this->admin())
            ->get(route('noticias-admin.edit', $noticia))->assertOk()->getContent();

        $this->assertMatchesRegularExpression(
            '#<option value="0"[^>]*selected[^>]*>\s*No: solo aparece#',
            $html,
        );
    }

    public function test_se_guarda_la_eleccion_al_crear(): void
    {
        $this->actingAs($this->admin())->post(route('noticias-admin.store'), [
            'titulo' => 'SIN VENTANA '.uniqid(),
            'contenido' => 'Cuerpo.',
            'fecha_publicacion' => Carbon::today()->toDateString(),
            'publicado' => '1',
            'destacada' => '0',
        ])->assertRedirect();

        $this->assertFalse(Noticia::latest('id')->firstOrFail()->destacada);
    }

    public function test_se_guarda_la_eleccion_al_editar(): void
    {
        $noticia = $this->noticia(['destacada' => true]);

        $this->actingAs($this->admin())->put(route('noticias-admin.update', $noticia), [
            'titulo' => $noticia->titulo,
            'contenido' => $noticia->contenido,
            'fecha_publicacion' => $noticia->fecha_publicacion->toDateString(),
            'publicado' => '1',
            'destacada' => '0',
        ])->assertRedirect();

        $this->assertFalse($noticia->fresh()->destacada);
    }

    public function test_la_lista_señala_cual_esta_saltando(): void
    {
        $saltando = $this->noticia(['destacada' => true]);
        $marcadaVieja = $this->noticia([
            'destacada' => true,
            'fecha_publicacion' => Carbon::today()->subDays(Noticia::DIAS_DESTACADA + 2)->toDateString(),
        ]);
        $sinMarcar = $this->noticia(['destacada' => false]);

        $html = $this->actingAs($this->admin())
            ->get(route('noticias-admin.index'))->assertOk()->getContent();

        $this->assertStringContainsString('Saltando ahora', $html);
        $this->assertStringContainsString('Marcada', $html);
        $this->assertSame(
            1,
            substr_count($html, 'Saltando ahora'),
            'Solo una puede estar saltando a la vez.',
        );
        $this->assertSame($saltando->id, Noticia::paraLaVentana()?->id);
        $this->assertTrue($marcadaVieja->destacada);
        $this->assertFalse($sinMarcar->destacada);
    }
}
