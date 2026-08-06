<?php

namespace App\Http\Controllers;

use App\Http\Requests\CuentaBancariaRequest;
use App\Models\Configuracion;
use App\Models\CuentaBancaria;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ConfiguracionController extends Controller
{
    public function index()
    {
        $cuentasBancarias = CuentaBancaria::orderBy('banco')->get();
        $whatsappCorporacion = Configuracion::obtener(Configuracion::WHATSAPP_CORPORACION);
        $nombreSistema = Configuracion::obtener(Configuracion::NOMBRE_SISTEMA, Configuracion::NOMBRE_SISTEMA_DEFECTO);
        $logoPath = Configuracion::obtener(Configuracion::LOGO_PATH);
        $logoUrl = $logoPath ? Storage::disk('public')->url($logoPath) : null;
        $descripcionPortada = Configuracion::obtener(Configuracion::DESCRIPCION_PORTADA, Configuracion::DESCRIPCION_PORTADA_DEFECTO);

        return view('configuracion.index', compact('cuentasBancarias', 'whatsappCorporacion', 'nombreSistema', 'logoUrl', 'descripcionPortada'));
    }

    public function actualizarWhatsapp(Request $request)
    {
        $request->validate([
            'whatsapp_corporacion' => ['nullable', 'string', 'max:20'],
        ]);

        Configuracion::guardar(Configuracion::WHATSAPP_CORPORACION, $request->input('whatsapp_corporacion'));

        return back()->with('status', 'Numero de WhatsApp actualizado.');
    }

    public function actualizarNombre(Request $request)
    {
        $request->validate([
            'nombre_sistema' => ['required', 'string', 'max:100'],
            'descripcion_portada' => ['nullable', 'string', 'max:500'],
            'logo' => ['nullable', 'image', 'max:2048'],
        ]);

        Configuracion::guardar(Configuracion::NOMBRE_SISTEMA, $request->input('nombre_sistema'));
        Configuracion::guardar(Configuracion::DESCRIPCION_PORTADA, $request->input('descripcion_portada'));

        if ($request->hasFile('logo')) {
            $anterior = Configuracion::obtener(Configuracion::LOGO_PATH);
            if ($anterior) {
                Storage::disk('public')->delete($anterior);
            }

            $ruta = $request->file('logo')->store('logos', 'public');
            Configuracion::guardar(Configuracion::LOGO_PATH, $ruta);
        }

        return back()->with('status', 'Nombre del sistema actualizado.');
    }

    public function guardarCuenta(CuentaBancariaRequest $request)
    {
        CuentaBancaria::create($request->validated());

        return back()->with('status', 'Cuenta bancaria agregada.');
    }

    public function actualizarCuenta(CuentaBancariaRequest $request, CuentaBancaria $cuentaBancaria)
    {
        $cuentaBancaria->update($request->validated());

        return back()->with('status', 'Cuenta bancaria actualizada.');
    }

    public function eliminarCuenta(CuentaBancaria $cuentaBancaria)
    {
        $cuentaBancaria->delete();

        return back()->with('status', 'Cuenta bancaria eliminada.');
    }
}
