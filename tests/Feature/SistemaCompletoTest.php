<?php

namespace Tests\Feature;

use App\Models\CierreCaja;
use App\Models\Equipo;
use App\Models\Noticia;
use App\Models\Pago;
use App\Models\Partido;
use App\Models\Socio;
use App\Models\TipoCargo;
use App\Models\Torneo;
use App\Models\User;
use App\Services\ReporteService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * Revision general del sistema: que todas las pantallas respondan, que
 * los reportes se generen y que las piezas internas esten sanas.
 */
class SistemaCompletoTest extends TestCase
{
    // ---------- Todas las pantallas ----------

    /**
     * Recorre TODAS las rutas GET con parametros resolubles y comprueba
     * que ninguna este rota. Es la red de seguridad mas amplia: si un
     * modulo rompio a otro, lo normal es que se caiga aqui.
     */
    public function test_todas_las_pantallas_responden(): void
    {
        DB::beginTransaction();

        $socio = Socio::firstOrFail();
        $equipo = Equipo::has('socios')->firstOrFail();

        // Se crean los registros que falten para que no quede ninguna ruta
        // sin revisar por no haber datos de ese tipo.
        $noticia = Noticia::first() ?? Noticia::create([
            'titulo' => 'Noticia de verificacion', 'contenido' => 'Contenido.',
            'fecha_publicacion' => today(), 'publicado' => true,
        ]);
        $cierre = CierreCaja::first() ?? CierreCaja::create([
            'fecha' => '2030-06-15', 'total_efectivo' => 0, 'total_transferencia' => 0,
            'total_ingresos' => 0, 'total_gastos' => 0, 'total_neto_efectivo' => 0,
        ]);
        $partido = Partido::first() ?? Partido::create([
            'torneo_id' => Torneo::firstOrFail()->id,
            'equipo_local_id' => $equipo->id,
            'equipo_visitante_id' => Equipo::where('id', '!=', $equipo->id)->firstOrFail()->id,
            'fecha_hora' => '2030-06-15 10:00', 'estado' => Partido::ESTADO_PROGRAMADO,
        ]);

        $sustituciones = [
            'socio' => $socio->getRouteKey(),
            'equipo' => $equipo->getRouteKey(),
            'torneo' => Torneo::first()?->getRouteKey(),
            'tipoCargo' => TipoCargo::first()?->getRouteKey(),
            'partido' => $partido->getRouteKey(),
            'noticia' => $noticia->getRouteKey(),
            'cierreCaja' => $cierre->getRouteKey(),
            'cargo' => $socio->cargos()->first()?->getRouteKey(),
            'pago' => Pago::first()?->getRouteKey(),
        ];

        $revisadas = 0;
        $saltadas = [];

        foreach (Route::getRoutes() as $ruta) {
            if (! in_array('GET', $ruta->methods(), true) || ! $ruta->getName()) {
                continue;
            }
            if (str_starts_with($ruta->uri(), 'storage/') || $ruta->uri() === 'up') {
                continue;
            }

            $url = $ruta->uri();
            $resoluble = true;

            preg_match_all('/\{(\w+)\??\}/', $url, $m);
            foreach ($m[1] as $parametro) {
                $valor = $sustituciones[$parametro] ?? null;
                if ($valor === null) {
                    $resoluble = false;
                    break;
                }
                $url = preg_replace('/\{'.$parametro.'\??\}/', (string) $valor, $url);
            }

            if (! $resoluble) {
                $saltadas[] = $ruta->getName();

                continue;
            }

            $respuesta = $this->actingAs(User::firstOrFail())->get('/'.ltrim($url, '/'));

            $this->assertContains(
                $respuesta->getStatusCode(),
                [200, 302],
                "La ruta {$ruta->getName()} ({$url}) respondio {$respuesta->getStatusCode()}."
            );
            $revisadas++;
        }

        DB::rollBack();

        $this->assertGreaterThanOrEqual(25, $revisadas, 'Se esperaban revisar al menos 25 pantallas.');
        $this->assertEmpty($saltadas, 'Quedaron rutas sin poder revisar: '.implode(', ', $saltadas));
    }

    public function test_las_pantallas_publicas_responden_sin_sesion(): void
    {
        foreach ([route('home'), route('consulta.index'), route('noticias-publicas.index'), route('login')] as $url) {
            $this->get($url)->assertOk();
        }
    }

    public function test_las_pantallas_privadas_exigen_sesion(): void
    {
        foreach ([
            route('socios.index'), route('equipos.index'), route('partidos.index'),
            route('partidos.posiciones'), route('cuota-moderada.index'), route('cierre-caja.index'),
            route('configuracion.index'), route('importar.index'), route('noticias-admin.index'),
        ] as $url) {
            $this->get($url)->assertRedirect(route('login'));
        }
    }

    // ---------- Reportes ----------

    public function test_todos_los_reportes_se_generan(): void
    {
        $equipo = Equipo::has('socios')->firstOrFail();
        $servicio = app(ReporteService::class);

        $this->assertNotEmpty($servicio->socioPdf(Socio::firstOrFail())->output(), 'PDF de socio');
        $this->assertNotEmpty($servicio->equipoPdf($equipo)->output(), 'PDF de equipo');
        $this->assertNotEmpty($servicio->planillaPagosPdf($equipo)->output(), 'Planilla de pagos');

        $otro = Equipo::where('id', '!=', $equipo->id)->firstOrFail();
        $this->assertNotEmpty(
            $servicio->planillaJuegoPdf($equipo, $otro, ['fecha' => '2026-01-01', 'hora' => '10:00'])->output(),
            'Planilla de juego'
        );
    }

    // ---------- Integridad interna ----------

    public function test_todas_las_migraciones_estan_aplicadas(): void
    {
        $pendientes = collect(glob(database_path('migrations/*.php')))
            ->map(fn ($ruta) => basename($ruta, '.php'))
            ->reject(fn ($nombre) => DB::table('migrations')->where('migration', $nombre)->exists());

        $this->assertEmpty($pendientes->all(), 'Migraciones sin aplicar: '.$pendientes->implode(', '));
    }

    public function test_no_hay_cargos_ni_pagos_huerfanos(): void
    {
        $this->assertEquals(0, DB::table('cargos')->whereNotNull('socio_id')
            ->whereNotIn('socio_id', DB::table('socios')->pluck('id'))->count(), 'Cargos sin socio.');

        $this->assertEquals(0, DB::table('pagos')->whereNotNull('socio_id')
            ->whereNotIn('socio_id', DB::table('socios')->pluck('id'))->count(), 'Pagos sin socio.');

        $this->assertEquals(0, DB::table('incidencias_partido')
            ->whereNotIn('partido_id', DB::table('partidos')->pluck('id'))->count(), 'Incidencias sin partido.');
    }

    public function test_los_tipos_de_cargo_de_tarjetas_existen(): void
    {
        // De estos dependen el cobro de tarjetas y el registro de partidos.
        foreach (['Amarillas', 'Rojas'] as $nombre) {
            $this->assertNotNull(
                TipoCargo::where('nombre', $nombre)->first(),
                "Falta el tipo de cargo '{$nombre}': el cobro de tarjetas quedaria sin efecto."
            );
        }
    }

    public function test_las_constantes_de_estado_coinciden_con_la_base(): void
    {
        $enBase = DB::table('socios')->distinct()->pluck('estado')->all();

        foreach ($enBase as $estado) {
            $this->assertContains($estado, Socio::ESTADOS, "El estado '{$estado}' existe en la base pero no en el modelo.");
        }

        $carnets = DB::table('socios')->distinct()->pluck('carnet')->all();
        foreach ($carnets as $carnet) {
            $this->assertArrayHasKey($carnet, Socio::CARNETS, "El carnet '{$carnet}' no esta definido en el modelo.");
        }
    }

    public function test_el_menu_apunta_a_rutas_que_existen(): void
    {
        $html = $this->actingAs(User::firstOrFail())->get(route('socios.index'))->getContent();
        preg_match('/<nav class="mainnav">(.*?)<\/nav>/s', $html, $m);

        preg_match_all('/href="([^"]+)"/', $m[1], $enlaces);

        foreach ($enlaces[1] as $url) {
            $ruta = parse_url($url, PHP_URL_PATH) ?: '/';
            $this->actingAs(User::firstOrFail())->get($ruta)->assertOk("El menu apunta a {$ruta}, que no responde.");
        }

        $this->assertGreaterThanOrEqual(14, count($enlaces[1]), 'El menu perdio accesos.');
    }
}
