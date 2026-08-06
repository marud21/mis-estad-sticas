<?php

namespace App\Http\Controllers;

use App\Models\Configuracion;
use App\Models\CuentaBancaria;
use App\Models\Socio;
use Illuminate\Http\Request;

class ConsultaPublicaController extends Controller
{
    public function index()
    {
        return view('consulta.index', [
            'socio' => null,
            'buscado' => false,
            'cuentasBancarias' => CuentaBancaria::orderBy('banco')->get(),
            'whatsappCorporacion' => Configuracion::obtener(Configuracion::WHATSAPP_CORPORACION),
        ]);
    }

    public function consultar(Request $request)
    {
        $request->validate([
            'numero_documento' => ['required', 'string', 'max:50'],
        ]);

        $socio = Socio::with(['cargos.tipoCargo', 'pagos.cargo.tipoCargo'])
            ->where('numero_documento', $request->input('numero_documento'))
            ->first();

        return view('consulta.index', [
            'socio' => $socio,
            'buscado' => true,
            'cuentasBancarias' => CuentaBancaria::orderBy('banco')->get(),
            'whatsappCorporacion' => Configuracion::obtener(Configuracion::WHATSAPP_CORPORACION),
        ]);
    }
}
