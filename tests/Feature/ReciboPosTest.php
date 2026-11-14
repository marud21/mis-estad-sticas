<?php

namespace Tests\Feature;

use App\Models\Pago;
use App\Models\Socio;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * El recibo que sale por la impresora POS.
 *
 * Lo que se cuida aqui es que salga completo y que se pueda leer: en papel
 * termico los trazos finos se queman palidos, asi que el recibo no puede
 * volver a una letra fina ni a tamaños de pantalla.
 */
class ReciboPosTest extends TestCase
{
    private function admin(): User
    {
        return User::where('role', User::ROLE_ADMIN)->firstOrFail();
    }

    /** Un socio y un pago de mentira, dentro de una transaccion que se deshace. */
    private function pagoDePrueba(string $nombre = 'JUAN DE LA CRUZ PRUEBA', float $valor = 45000): Pago
    {
        $socio = Socio::create([
            'nombre_completo' => $nombre,
            'numero_documento' => uniqid('r'),
            'fecha_nacimiento' => '1990-01-01',
            'entidad_salud' => 'X',
            'celular' => '3000000000',
            'tipo_sangre' => 'O+',
            'direccion_residencia' => 'X',
            'posicion_juego' => 'Medio',
            'nivel_jugador' => 2,
            'estado' => 'activo',
        ]);

        return Pago::create([
            'socio_id' => $socio->id,
            'valor' => $valor,
            'fecha' => '2030-04-15',
            'tipo' => 'efectivo',
        ]);
    }

    public function test_el_recibo_pide_sesion(): void
    {
        DB::beginTransaction();
        $pago = $this->pagoDePrueba();

        $this->get(route('pagos.recibo', $pago))->assertRedirect(route('login'));

        DB::rollBack();
    }

    public function test_el_recibo_trae_todos_los_datos_del_pago(): void
    {
        DB::beginTransaction();
        $pago = $this->pagoDePrueba('MARIA FERNANDA RESTREPO OSORIO', 45000);

        $html = $this->actingAs($this->admin())
            ->get(route('pagos.recibo', $pago))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('MARIA FERNANDA RESTREPO OSORIO', $html);
        $this->assertStringContainsString($pago->socio->numero_documento, $html);
        $this->assertStringContainsString(str_pad($pago->id, 6, '0', STR_PAD_LEFT), $html);
        $this->assertStringContainsString('15/04/2030', $html);
        $this->assertStringContainsString('$45.000', $html);
        $this->assertStringContainsString('Efectivo', $html);
        $this->assertStringContainsString('TOTAL PAGADO', $html);

        DB::rollBack();
    }

    public function test_la_letra_es_gruesa_y_no_una_fina_de_maquina_de_escribir(): void
    {
        DB::beginTransaction();
        $pago = $this->pagoDePrueba();

        $html = $this->actingAs($this->admin())
            ->get(route('pagos.recibo', $pago))
            ->assertOk()
            ->getContent();

        // Se miran solo las declaraciones de letra, no todo el archivo: la
        // palabra "Courier" aparece tambien en el comentario que explica
        // por que se dejo de usar, y eso no es un defecto.
        preg_match_all('/font-family:\s*([^;}]+)/i', $html, $m);
        $familias = $m[1] ?? [];
        $this->assertNotEmpty($familias, 'El recibo no declara ninguna letra.');

        foreach ($familias as $familia) {
            // Courier y las monoespaciadas son de trazo fino: en papel
            // termico salen palidas y entrecortadas.
            $this->assertDoesNotMatchRegularExpression(
                '/courier|monospace/i',
                $familia,
                "El recibo POS no debe volver a una letra de trazo fino, y declara: {$familia}",
            );
        }

        $this->assertMatchesRegularExpression(
            '/font-family:\s*Arial/',
            $html,
            'El recibo debe imprimirse en palo seco.',
        );
        $this->assertMatchesRegularExpression(
            '/font-weight:\s*700/',
            $html,
            'El cuerpo del recibo va en negrita para que queme bien.',
        );

        DB::rollBack();
    }

    public function test_los_tamaños_van_en_puntos_de_impresion_y_no_en_pixeles(): void
    {
        DB::beginTransaction();
        $pago = $this->pagoDePrueba();

        $html = $this->actingAs($this->admin())
            ->get(route('pagos.recibo', $pago))
            ->assertOk()
            ->getContent();

        // Se mira solo el bloque de estilos: el boton de la pantalla si
        // puede ir en pixeles, porque ese no se imprime.
        preg_match('/<style>(.*?)<\/style>/s', $html, $m);
        $estilos = $m[1] ?? '';
        $this->assertNotSame('', $estilos, 'No se encontro el bloque de estilos.');

        $this->assertMatchesRegularExpression(
            '/body\s*\{[^}]*font-size:\s*11pt/s',
            $estilos,
            'El cuerpo debe medirse en puntos, que es lo que entiende la impresora.',
        );
        $this->assertMatchesRegularExpression(
            '/\.total \.valor\s*\{[^}]*font-size:\s*20pt/s',
            $estilos,
            'El total es lo que la gente busca primero: va en grande.',
        );

        DB::rollBack();
    }

    public function test_un_nombre_muy_largo_parte_renglon_en_vez_de_desbordar(): void
    {
        DB::beginTransaction();
        $pago = $this->pagoDePrueba('MARIA DE LOS ANGELES BETANCOURT VILLALOBOS DE RESTREPO');

        $html = $this->actingAs($this->admin())
            ->get(route('pagos.recibo', $pago))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('MARIA DE LOS ANGELES BETANCOURT VILLALOBOS DE RESTREPO', $html);
        $this->assertStringContainsString('word-break: break-word', $html);

        DB::rollBack();
    }

    public function test_el_recibo_cabe_en_el_rollo_de_ochenta_milimetros(): void
    {
        DB::beginTransaction();
        $pago = $this->pagoDePrueba();

        $html = $this->actingAs($this->admin())
            ->get(route('pagos.recibo', $pago))
            ->assertOk()
            ->getContent();

        // El margen de @page sumaba al ancho del cuerpo y recortaba el
        // borde derecho en rollos justos: ahora lo pone el cuerpo.
        $this->assertMatchesRegularExpression('/@page\s*\{[^}]*margin:\s*0/s', $html);
        $this->assertMatchesRegularExpression('/width:\s*72mm/', $html);

        // Y el relleno tiene que ir DENTRO de esos 72 mm. Sin esto el
        // cuerpo media 76 mm, no cabia en el area imprimible del rollo y
        // la impresora encogia el recibo entero: justo lo contrario de
        // lo que se busca.
        $this->assertMatchesRegularExpression(
            '/box-sizing:\s*border-box/',
            $html,
            'El relleno no puede sumarse al ancho del rollo.',
        );

        DB::rollBack();
    }

    public function test_el_boton_y_la_firma_no_salen_impresos(): void
    {
        DB::beginTransaction();
        $pago = $this->pagoDePrueba();

        $html = $this->actingAs($this->admin())
            ->get(route('pagos.recibo', $pago))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('class="btn-imprimir no-print"', $html);
        $this->assertMatchesRegularExpression(
            '/@media print\s*\{[^}]*\.no-print\s*\{\s*display:\s*none/s',
            $html,
            'Lo que es de pantalla no debe gastar papel.',
        );

        DB::rollBack();
    }
}
