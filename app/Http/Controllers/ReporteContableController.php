<?php

namespace App\Http\Controllers;

use App\Http\Requests\ReporteContableRequest;
use App\Services\ReporteService;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReporteContableController extends Controller
{
    public function __construct(private readonly ReporteService $reportes)
    {
    }

    public function index()
    {
        return view('reportes-contables.index');
    }

    public function exportar(ReporteContableRequest $request)
    {
        [$desde, $hasta, $etiqueta] = $this->resolverRango($request);

        $pdf = $this->reportes->contablePdf($desde, $hasta, $etiqueta);

        return $pdf->download($this->nombreArchivo($desde, $hasta, 'pdf'));
    }

    public function exportarExcel(ReporteContableRequest $request): StreamedResponse
    {
        [$desde, $hasta] = $this->resolverRango($request);

        [$cargos, $pagos, $totalCargos, $totalPagos] = $this->reportes->consultarCargosYPagos($desde, $hasta);

        $nombreArchivo = $this->nombreArchivo($desde, $hasta, 'csv');

        return response()->streamDownload(function () use ($cargos, $pagos, $totalCargos, $totalPagos) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");

            fputcsv($out, ['CARGOS'], ';');
            fputcsv($out, ['Fecha', 'Socio', 'Documento', 'Tipo de cargo', 'Monto'], ';');
            foreach ($cargos as $cargo) {
                fputcsv($out, [
                    $cargo->fecha_fmt,
                    $cargo->socio_nombre,
                    $cargo->numero_documento,
                    $cargo->tipo_nombre,
                    number_format((float) $cargo->monto, 2, ',', ''),
                ], ';');
            }

            fputcsv($out, [], ';');
            fputcsv($out, ['PAGOS'], ';');
            fputcsv($out, ['Fecha', 'Socio', 'Documento', 'Tipo de pago', 'Equipo', 'Abona a', 'Valor'], ';');
            foreach ($pagos as $pago) {
                fputcsv($out, [
                    $pago->fecha_fmt,
                    $pago->socio_nombre,
                    $pago->numero_documento,
                    ucfirst($pago->tipo),
                    $pago->equipo_nombre ?? '',
                    $pago->cargo_nombre ?? '',
                    number_format((float) $pago->valor, 2, ',', ''),
                ], ';');
            }

            fputcsv($out, [], ';');
            fputcsv($out, ['Total cargos', number_format($totalCargos, 2, ',', '')], ';');
            fputcsv($out, ['Total pagos', number_format($totalPagos, 2, ',', '')], ';');
            fputcsv($out, ['Balance', number_format($totalPagos - $totalCargos, 2, ',', '')], ';');

            fclose($out);
        }, $nombreArchivo, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    private function resolverRango(ReporteContableRequest $request): array
    {
        return match ($request->validated('tipo')) {
            'dia' => $this->rangoDia($request->validated('fecha')),
            'mes' => $this->rangoMes($request->validated('mes')),
            'rango' => $this->rangoFechas($request->validated('fecha_inicio'), $request->validated('fecha_fin')),
        };
    }

    private function nombreArchivo(CarbonInterface $desde, CarbonInterface $hasta, string $extension): string
    {
        return "reporte-contable-{$desde->format('Y-m-d')}-a-{$hasta->format('Y-m-d')}.{$extension}";
    }

    private function rangoDia(string $fecha): array
    {
        $dia = Carbon::parse($fecha)->startOfDay();

        return [$dia->copy(), $dia->copy()->endOfDay(), 'Dia: ' . $dia->format('d/m/Y')];
    }

    private function rangoMes(string $mes): array
    {
        $inicio = Carbon::createFromFormat('Y-m', $mes)->startOfMonth();

        return [$inicio->copy(), $inicio->copy()->endOfMonth(), 'Mes: ' . $inicio->translatedFormat('F Y')];
    }

    private function rangoFechas(string $inicio, string $fin): array
    {
        $desde = Carbon::parse($inicio)->startOfDay();
        $hasta = Carbon::parse($fin)->endOfDay();

        return [$desde, $hasta, 'Del ' . $desde->format('d/m/Y') . ' al ' . $hasta->format('d/m/Y')];
    }
}
