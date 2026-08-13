<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\PasswordController;
use App\Http\Controllers\CargoController;
use App\Http\Controllers\CierreCajaController;
use App\Http\Controllers\ConfiguracionController;
use App\Http\Controllers\ConsultaPublicaController;
use App\Http\Controllers\EquipoController;
use App\Http\Controllers\ImportController;
use App\Http\Controllers\PagoController;
use App\Http\Controllers\PlanillaJuegoController;
use App\Http\Controllers\ReporteContableController;
use App\Http\Controllers\ReporteController;
use App\Http\Controllers\SocioController;
use App\Http\Controllers\TipoCargoController;
use App\Http\Controllers\TorneoController;
use Illuminate\Support\Facades\Route;

Route::view('/', 'home')->name('home');

Route::get('login', [LoginController::class, 'create'])->name('login')->middleware('guest');
Route::post('login', [LoginController::class, 'store'])->name('login.store')->middleware('guest');
Route::post('logout', [LoginController::class, 'destroy'])->name('logout')->middleware('auth');

Route::get('consulta', [ConsultaPublicaController::class, 'index'])->name('consulta.index');
Route::post('consulta', [ConsultaPublicaController::class, 'consultar'])->name('consulta.buscar');

Route::middleware('auth')->group(function () {
    Route::redirect('/panel', '/socios');

    Route::resource('socios', SocioController::class);
    Route::patch('socios/{socio}/estado', [SocioController::class, 'cambiarEstado'])->name('socios.estado');
    Route::get('socios/{socio}/reporte', [ReporteController::class, 'socio'])->name('socios.reporte');
    Route::get('socios/{socio}/whatsapp', [ReporteController::class, 'whatsappSocio'])->name('socios.whatsapp');

    Route::post('socios/{socio}/cargos', [CargoController::class, 'store'])->name('socios.cargos.store');
    Route::get('socios/{socio}/cargos/{cargo}/edit', [CargoController::class, 'edit'])->name('socios.cargos.edit');
    Route::put('socios/{socio}/cargos/{cargo}', [CargoController::class, 'update'])->name('socios.cargos.update');
    Route::delete('socios/{socio}/cargos/{cargo}', [CargoController::class, 'destroy'])->name('socios.cargos.destroy');

    Route::post('socios/{socio}/pagos', [PagoController::class, 'store'])->name('socios.pagos.store');
    Route::delete('socios/{socio}/pagos/{pago}', [PagoController::class, 'destroy'])->name('socios.pagos.destroy');
    Route::get('pagos/{pago}/recibo', [PagoController::class, 'recibo'])->name('pagos.recibo');

    Route::resource('equipos', EquipoController::class);
    Route::post('equipos/{equipo}/socios', [EquipoController::class, 'agregarSocio'])->name('equipos.socios.store');
    Route::delete('equipos/{equipo}/socios/{socio}', [EquipoController::class, 'quitarSocio'])->name('equipos.socios.destroy');
    Route::post('equipos/{equipo}/pagos-multiples', [PagoController::class, 'ejecutarMultiples'])->name('equipos.pagos.ejecutar');
    Route::get('equipos/{equipo}/reporte', [ReporteController::class, 'equipo'])->name('equipos.reporte');
    Route::get('equipos/{equipo}/planilla-pagos', [ReporteController::class, 'planillaPagos'])->name('equipos.planilla-pagos');

    Route::resource('tipos-cargo', TipoCargoController::class)->except(['show'])->parameters(['tipos-cargo' => 'tipoCargo']);
    Route::post('tipos-cargo/{tipoCargo}/aplicar-masivo', [TipoCargoController::class, 'aplicarMasivo'])->name('tipos-cargo.aplicar-masivo');

    Route::resource('torneos', TorneoController::class);

    Route::get('planilla-juego', [PlanillaJuegoController::class, 'index'])->name('planilla-juego.index');
    Route::post('planilla-juego', [PlanillaJuegoController::class, 'generar'])->name('planilla-juego.generar');

    Route::get('reportes-contables', [ReporteContableController::class, 'index'])->name('reportes-contables.index');
    Route::post('reportes-contables', [ReporteContableController::class, 'exportar'])->name('reportes-contables.exportar');
    Route::post('reportes-contables/excel', [ReporteContableController::class, 'exportarExcel'])->name('reportes-contables.exportar-excel');

    Route::get('cierre-caja', [CierreCajaController::class, 'index'])->name('cierre-caja.index');
    Route::get('cierre-caja/nuevo', [CierreCajaController::class, 'create'])->name('cierre-caja.create');
    Route::post('cierre-caja', [CierreCajaController::class, 'store'])->name('cierre-caja.store');
    Route::get('cierre-caja/{cierreCaja}', [CierreCajaController::class, 'show'])->name('cierre-caja.show');

    Route::get('password', [PasswordController::class, 'edit'])->name('password.edit');
    Route::put('password', [PasswordController::class, 'update'])->name('password.update');

    Route::get('importar', [ImportController::class, 'index'])->name('importar.index');
    Route::post('importar/socios', [ImportController::class, 'socios'])->name('importar.socios');
    Route::post('importar/cargos', [ImportController::class, 'cargos'])->name('importar.cargos');
    Route::post('importar/pagos', [ImportController::class, 'pagos'])->name('importar.pagos');

    Route::get('configuracion', [ConfiguracionController::class, 'index'])->name('configuracion.index');
    Route::post('configuracion/nombre', [ConfiguracionController::class, 'actualizarNombre'])->name('configuracion.nombre');
    Route::put('configuracion/whatsapp', [ConfiguracionController::class, 'actualizarWhatsapp'])->name('configuracion.whatsapp');
    Route::post('configuracion/cuentas-bancarias', [ConfiguracionController::class, 'guardarCuenta'])->name('configuracion.cuentas.store');
    Route::put('configuracion/cuentas-bancarias/{cuentaBancaria}', [ConfiguracionController::class, 'actualizarCuenta'])->name('configuracion.cuentas.update');
    Route::delete('configuracion/cuentas-bancarias/{cuentaBancaria}', [ConfiguracionController::class, 'eliminarCuenta'])->name('configuracion.cuentas.destroy');
});
