{{--
    Los parametros del panel. El rango de fechas manda sobre el dinero y
    la actividad; los demas filtros acotan a un torneo, una categoria o
    un equipo.
--}}
<div class="card panel-filtros">
    <form method="GET" action="{{ route('panel.index') }}">
        <div class="grid-5">
            <div>
                <label for="desde">Desde</label>
                <input type="date" name="desde" id="desde" value="{{ $filtro->desde->toDateString() }}">
            </div>
            <div>
                <label for="hasta">Hasta</label>
                <input type="date" name="hasta" id="hasta" value="{{ $filtro->hasta->toDateString() }}">
            </div>
            <div>
                <label for="torneo_id">Torneo</label>
                <select name="torneo_id" id="torneo_id">
                    <option value="">Todos</option>
                    @foreach ($torneos as $t)
                        <option value="{{ $t->id }}" @selected($filtro->torneoId === $t->id)>{{ $t->nombre }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="categoria">Categoria</label>
                <select name="categoria" id="categoria">
                    <option value="">Todas</option>
                    @foreach ($categorias as $c)
                        <option value="{{ $c }}" @selected($filtro->categoria === (string) $c)>{{ $c }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="equipo_id">Equipo</label>
                <select name="equipo_id" id="equipo_id">
                    <option value="">Todos</option>
                    @foreach ($equipos as $e)
                        <option value="{{ $e->id }}" @selected($filtro->equipoId === $e->id)>{{ $e->nombre }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <div class="panel-filtros-pie">
            <button class="btn" type="submit">Aplicar</button>
            <a class="btn btn-secondary" href="{{ route('panel.index') }}">Limpiar</a>

            {{-- Atajos, que es lo que se usa el 90% de las veces. --}}
            <span class="atajos">
                <a href="{{ route('panel.index', ['desde' => now()->startOfMonth()->toDateString(), 'hasta' => now()->endOfMonth()->toDateString()]) }}">Este mes</a>
                <a href="{{ route('panel.index', ['desde' => now()->subMonths(2)->startOfMonth()->toDateString(), 'hasta' => now()->endOfMonth()->toDateString()]) }}">Ultimos 3 meses</a>
                <a href="{{ route('panel.index', ['desde' => now()->startOfYear()->toDateString(), 'hasta' => now()->endOfYear()->toDateString()]) }}">Este año</a>
            </span>

            <span class="panel-rango">Periodo: <strong>{{ $filtro->etiquetaRango() }}</strong></span>
        </div>
    </form>
</div>
