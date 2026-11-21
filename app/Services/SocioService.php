<?php

namespace App\Services;

use App\Models\Cargo;
use App\Models\Equipo;
use App\Models\Socio;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class SocioService
{
    public function crear(array $datos, array $cargosIniciales = [], ?int $equipoId = null): Socio
    {
        return DB::transaction(function () use ($datos, $cargosIniciales, $equipoId) {
            $socio = Socio::create($datos);

            if ($equipoId) {
                $socio->equipos()->syncWithoutDetaching([$equipoId]);
            }

            // Los cargos iniciales quedan asociados al mismo equipo (y su
            // torneo actual) con el que se registro al socio, si se eligio uno.
            $torneoId = $equipoId ? Equipo::find($equipoId)?->torneo_id : null;

            foreach ($cargosIniciales as $cargo) {
                $socio->cargos()->create([
                    'tipo_cargo_id' => $cargo['tipo_cargo_id'],
                    'equipo_id' => $equipoId,
                    'torneo_id' => $torneoId,
                    'monto' => $cargo['monto'],
                    'fecha' => $cargo['fecha'],
                    'descripcion' => $cargo['descripcion'] ?? null,
                ]);
            }

            return $socio;
        });
    }

    public function actualizar(Socio $socio, array $datos, ?int $equipoId = null, bool $equipoEnviado = false): Socio
    {
        $socio->update($datos);

        // Un socio puede pertenecer a varios equipos: este campo solo
        // agrega el equipo seleccionado sin quitar los demas. Para retirar
        // un equipo se usa el boton "Quitar" en la pantalla de ese equipo.
        if ($equipoEnviado && $equipoId) {
            $socio->equipos()->syncWithoutDetaching([$equipoId]);
        }

        return $socio;
    }

    public function eliminar(Socio $socio): void
    {
        if ($socio->foto_path) {
            Storage::disk('public')->delete($socio->foto_path);
        }

        $socio->delete();
    }

    public function cambiarEstado(Socio $socio, string $estado): Socio
    {
        $datos = ['estado' => $estado];

        if ($estado !== $socio->estado) {
            $datos['fecha_cambio_estado'] = now()->toDateString();
        }

        // Un cambio manual de estado desvincula al socio de la suspension
        // automatica por inactividad del equipo, para que una futura
        // reactivacion del equipo no sobreescriba esta decision manual.
        $datos['suspendido_por_equipo'] = false;

        $socio->update($datos);

        return $socio;
    }

    /**
     * Actualiza el estado del carnet del socio (tiene / extraviado / no
     * tiene). Es un dato de control independiente del estado del socio:
     * no afecta sus cobros ni su pertenencia a un equipo.
     */
    public function cambiarCarnet(Socio $socio, string $carnet): Socio
    {
        $socio->update(['carnet' => $carnet]);

        return $socio;
    }

    /**
     * Pone el numero de camiseta y el carnet de varios socios de una vez,
     * tal como quedaron marcados en la planilla de un partido.
     *
     * Solo se tocan los socios que llegan en $jugadores. El formulario
     * manda identificadores y no hay que creerles: si alguien cambiara un
     * id en el envio, estaria editando a un socio que ni siquiera jugo.
     * Quien llama arma esa lista consultando los equipos del partido.
     *
     * El carnet tiene tres estados y la planilla solo tiene una casilla,
     * asi que la casilla maneja "tiene" y "no tiene". Al que estaba como
     * "extraviado" se le respeta: esa casilla no puede expresar ese estado
     * y borrarlo aqui seria perder, sin avisar, un dato que alguien puso a
     * proposito desde la ficha del socio.
     *
     * Solo se guarda al que de verdad cambia. Si no, registrar un partido
     * movería la fecha de actualizacion de veinte socios a los que nadie
     * les toco nada.
     *
     * @param  \Illuminate\Support\Collection<int, Socio>  $jugadores  los unicos que se pueden tocar
     * @param  array<int, array{numero: int|null, tiene_carnet: bool}>  $datos  por id de socio
     * @return int  cuantos socios quedaron modificados
     */
    public function actualizarDesdeLaPlanilla($jugadores, array $datos): int
    {
        $cambiados = 0;

        foreach ($jugadores as $socio) {
            if (! array_key_exists($socio->id, $datos)) {
                continue;
            }

            $fila = $datos[$socio->id];
            $nuevos = [];

            // El modelo no declara un cast para numero_camiseta, asi que
            // segun el driver puede llegar como entero o como texto. Se
            // normalizan los dos lados antes de comparar: si no, "10" y 10
            // se verian distintos y se guardaria un cambio que no existe.
            $numero = $fila['numero'];
            $actual = $socio->numero_camiseta === null || $socio->numero_camiseta === ''
                ? null
                : (int) $socio->numero_camiseta;

            if ($numero !== $actual) {
                $nuevos['numero_camiseta'] = $numero;
            }

            $carnet = $this->carnetSegunLaCasilla((string) $socio->carnet, $fila['tiene_carnet']);
            if ($carnet !== $socio->carnet) {
                $nuevos['carnet'] = $carnet;
            }

            if ($nuevos !== []) {
                $socio->update($nuevos);
                $cambiados++;
            }
        }

        return $cambiados;
    }

    /** Que carnet corresponde segun como quedo la casilla de la planilla. */
    private function carnetSegunLaCasilla(string $actual, bool $marcada): string
    {
        if ($marcada) {
            return Socio::CARNET_TIENE;
        }

        return $actual === Socio::CARNET_EXTRAVIADO
            ? Socio::CARNET_EXTRAVIADO
            : Socio::CARNET_NO_TIENE;
    }
}
