<?php

namespace Tests\Feature;

use App\Models\Noticia;
use App\Models\NoticiaAdjunto;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Los adjuntos de una noticia: se ven en pantalla y no se descargan.
 *
 * Lo que mas se cuida es que no quede ninguna puerta lateral al archivo.
 * Antes vivian en el disco publico, con una direccion fija bajo /storage
 * que seguia funcionando aunque la pagina no ofreciera ningun boton.
 * Ahora viven en el disco privado y solo salen por una ruta que
 * comprueba a quien le esta entregando que cosa.
 *
 * Nada de esto impide que alguien guarde lo que ya esta viendo: el
 * archivo llego a su navegador para poder mostrarse. Lo que se verifica
 * aqui es que no haya una descarga ofrecida ni una direccion suelta.
 *
 * Cada prueba corre dentro de una transaccion que se deshace al final,
 * porque la base de este equipo tiene los datos reales del club.
 */
class AdjuntosSinDescargaTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        DB::beginTransaction();
    }

    protected function tearDown(): void
    {
        // Los archivos de prueba se borran a mano: el rollBack deshace
        // las filas de la base, pero no toca el disco.
        foreach ($this->archivos as $ruta) {
            Storage::disk(NoticiaAdjunto::DISCO)->delete($ruta);
        }

        DB::rollBack();
        parent::tearDown();
    }

    /** @var array<int, string> */
    private array $archivos = [];

    private function admin(): User
    {
        return User::where('role', User::ROLE_ADMIN)->firstOrFail();
    }

    private function noticia(bool $publicada = true): Noticia
    {
        return Noticia::create([
            'titulo' => 'NOTICIA ADJUNTOS '.uniqid(),
            'contenido' => 'Contenido de prueba.',
            'fecha_publicacion' => now()->toDateString(),
            'publicado' => $publicada,
        ]);
    }

    /** Un adjunto de verdad: fila en la base y archivo en el disco. */
    private function adjunto(Noticia $noticia, string $nombre = 'documento.pdf', string $contenido = '%PDF-1.4 prueba'): NoticiaAdjunto
    {
        $ruta = 'noticias/adjuntos/'.uniqid().'-'.$nombre;
        Storage::disk(NoticiaAdjunto::DISCO)->put($ruta, $contenido);
        $this->archivos[] = $ruta;

        return $noticia->adjuntos()->create([
            'nombre_original' => $nombre,
            'ruta' => $ruta,
            'tipo_mime' => 'application/pdf',
            'tamano' => strlen($contenido),
        ]);
    }

    // ================= SE VE =================

    public function test_el_adjunto_se_entrega_para_verlo_en_pantalla(): void
    {
        $noticia = $this->noticia();
        $adjunto = $this->adjunto($noticia);

        $r = $this->get(route('noticias-publicas.adjunto', [$noticia, $adjunto]))->assertOk();

        $this->assertStringStartsWith(
            'inline',
            (string) $r->headers->get('Content-Disposition'),
            '"inline" es lo que le dice al navegador que lo muestre en vez de guardarlo.',
        );
        $this->assertSame('application/pdf', $r->headers->get('Content-Type'));
    }

    public function test_la_pantalla_ofrece_verlo(): void
    {
        $noticia = $this->noticia();
        $adjunto = $this->adjunto($noticia);

        $html = $this->get(route('noticias-publicas.show', $noticia))->assertOk()->getContent();

        $this->assertStringContainsString('Ver en pantalla', $html);

        // La direccion viaja dentro de un @js, que escapa las barras
        // ("http:\/\/..."). Se deshace ese escape antes de buscarla.
        $this->assertStringContainsString(
            route('noticias-publicas.adjunto', [$noticia, $adjunto]),
            str_replace('\\/', '/', $html),
        );
    }

    public function test_una_imagen_se_entrega_como_imagen(): void
    {
        $noticia = $this->noticia();
        $adjunto = $this->adjunto($noticia, 'foto.jpg', 'bytes de una foto');

        $this->get(route('noticias-publicas.adjunto', [$noticia, $adjunto]))
            ->assertOk()
            ->assertHeader('Content-Type', 'image/jpeg');
    }

    // ================= NO SE DESCARGA =================

    public function test_la_pantalla_no_ofrece_descargar(): void
    {
        $noticia = $this->noticia();
        $this->adjunto($noticia);

        $html = $this->get(route('noticias-publicas.show', $noticia))->assertOk()->getContent();

        $this->assertStringNotContainsString('Descargar', $html);
        $this->assertStringNotContainsString('download=', $html);
        $this->assertStringNotContainsString('Abrir en pestaña nueva', $html);
    }

    public function test_el_archivo_no_queda_en_el_disco_publico(): void
    {
        $noticia = $this->noticia();
        $adjunto = $this->adjunto($noticia);

        $this->assertTrue(
            Storage::disk(NoticiaAdjunto::DISCO)->exists($adjunto->ruta),
            'Esta en el disco privado.',
        );
        $this->assertFalse(
            Storage::disk('public')->exists($adjunto->ruta),
            'Y NO en el publico, que es el que se sirve solo bajo /storage.',
        );
    }

    public function test_la_direccion_del_adjunto_no_apunta_a_storage(): void
    {
        $noticia = $this->noticia();
        $adjunto = $this->adjunto($noticia);

        $this->assertStringNotContainsString(
            '/storage/',
            $adjunto->url,
            'Una direccion bajo /storage se puede pasar por ahi y la sirve el servidor sin preguntar nada.',
        );
    }

    // ================= QUIEN PUEDE VERLO =================

    public function test_el_adjunto_de_un_borrador_no_se_entrega_a_cualquiera(): void
    {
        $borrador = $this->noticia(publicada: false);
        $adjunto = $this->adjunto($borrador);

        $this->get(route('noticias-publicas.adjunto', [$borrador, $adjunto]))->assertNotFound();
    }

    public function test_quien_administra_noticias_si_ve_el_borrador(): void
    {
        $borrador = $this->noticia(publicada: false);
        $adjunto = $this->adjunto($borrador);

        $this->actingAs($this->admin())
            ->get(route('noticias-publicas.adjunto', [$borrador, $adjunto]))
            ->assertOk();
    }

    public function test_no_se_puede_sacar_el_adjunto_de_otra_noticia(): void
    {
        $publicada = $this->noticia();
        $borrador = $this->noticia(publicada: false);
        $escondido = $this->adjunto($borrador, 'secreto.pdf');

        // Se nombra la noticia publicada y el adjunto del borrador: si
        // los dos identificadores no se comprobaran juntos, pasaria.
        $this->get(route('noticias-publicas.adjunto', [$publicada, $escondido]))
            ->assertNotFound();
    }

    public function test_un_adjunto_que_ya_no_esta_en_el_disco_da_404(): void
    {
        $noticia = $this->noticia();
        $adjunto = $this->adjunto($noticia);
        Storage::disk(NoticiaAdjunto::DISCO)->delete($adjunto->ruta);

        $this->get(route('noticias-publicas.adjunto', [$noticia, $adjunto]))->assertNotFound();
    }

    // ================= SEGURIDAD DE LO QUE SE SIRVE =================

    public function test_un_xml_se_entrega_como_texto_y_no_se_interpreta(): void
    {
        $noticia = $this->noticia();
        $adjunto = $this->adjunto($noticia, 'planilla.xml', '<?xml version="1.0"?><a/>');

        $r = $this->get(route('noticias-publicas.adjunto', [$noticia, $adjunto]))->assertOk();

        $this->assertStringStartsWith(
            'text/plain',
            (string) $r->headers->get('Content-Type'),
            'Como XML el navegador lo interpreta, y un archivo mal intencionado podria ejecutar codigo en nuestro dominio.',
        );
    }

    public function test_el_tipo_lo_decide_la_extension_y_no_quien_sube(): void
    {
        $noticia = $this->noticia();
        $adjunto = $noticia->adjuntos()->create([
            'nombre_original' => 'documento.pdf',
            'ruta' => 'noticias/adjuntos/inventado.pdf',
            // Un tipo mentiroso, como lo mandaria un navegador manipulado.
            'tipo_mime' => 'text/html',
            'tamano' => 10,
        ]);
        Storage::disk(NoticiaAdjunto::DISCO)->put($adjunto->ruta, 'x');
        $this->archivos[] = $adjunto->ruta;

        $this->get(route('noticias-publicas.adjunto', [$noticia, $adjunto]))
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf');
    }

    public function test_el_navegador_no_adivina_el_tipo(): void
    {
        $noticia = $this->noticia();
        $adjunto = $this->adjunto($noticia);

        $r = $this->get(route('noticias-publicas.adjunto', [$noticia, $adjunto]))->assertOk();

        $this->assertSame('nosniff', $r->headers->get('X-Content-Type-Options'));
        $this->assertStringContainsString(
            "default-src 'none'",
            (string) $r->headers->get('Content-Security-Policy'),
        );
    }

    // ================= LOS QUE NO SE PUEDEN MOSTRAR =================

    public function test_un_word_se_anuncia_como_no_mostrable(): void
    {
        $noticia = $this->noticia();
        $this->adjunto($noticia, 'acta.docx', 'contenido');

        $html = $this->get(route('noticias-publicas.show', $noticia))->assertOk()->getContent();

        $this->assertStringContainsString('No se puede mostrar en pantalla', $html);
        $this->assertStringNotContainsString('Descargar', $html);
    }

    public function test_al_administrador_se_le_avisa_al_editar(): void
    {
        $noticia = $this->noticia();
        $this->adjunto($noticia, 'acta.docx', 'contenido');

        $html = $this->actingAs($this->admin())
            ->get(route('noticias-admin.edit', $noticia))->assertOk()->getContent();

        $this->assertStringContainsString('el publico no va a poder verlo', $html);
    }

    // ================= SUBIR Y BORRAR =================

    public function test_lo_que_se_sube_va_al_disco_privado(): void
    {
        $r = $this->actingAs($this->admin())->post(route('noticias-admin.store'), [
            'titulo' => 'CON ADJUNTO '.uniqid(),
            'contenido' => 'Cuerpo de la noticia.',
            'fecha_publicacion' => now()->toDateString(),
            'publicado' => '1',
            'adjuntos' => [UploadedFile::fake()->create('informe.pdf', 20, 'application/pdf')],
        ]);
        $r->assertRedirect();

        $adjunto = NoticiaAdjunto::latest('id')->firstOrFail();
        $this->archivos[] = $adjunto->ruta;

        $this->assertTrue(Storage::disk(NoticiaAdjunto::DISCO)->exists($adjunto->ruta));
        $this->assertFalse(Storage::disk('public')->exists($adjunto->ruta));
    }

    public function test_borrar_el_adjunto_borra_el_archivo(): void
    {
        $noticia = $this->noticia();
        $adjunto = $this->adjunto($noticia);
        $ruta = $adjunto->ruta;

        $this->actingAs($this->admin())
            ->delete(route('noticias-admin.adjuntos.destroy', [$noticia, $adjunto]))
            ->assertRedirect();

        $this->assertFalse(
            Storage::disk(NoticiaAdjunto::DISCO)->exists($ruta),
            'No puede quedar el archivo huerfano en el disco.',
        );
    }
}
