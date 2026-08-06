<div>
    <label>Nombre</label>
    <input type="text" name="nombre" value="{{ old('nombre', $tipoCargo->nombre ?? '') }}" required>
</div>
<div>
    <label>Monto por defecto</label>
    <input type="number" step="0.01" name="monto_default" value="{{ old('monto_default', $tipoCargo->monto_default ?? 0) }}" required>
</div>
<div>
    <label><input type="checkbox" name="es_recurrente" value="1" style="width:auto; display:inline-block;" @checked(old('es_recurrente', $tipoCargo->es_recurrente ?? false))> Es recurrente (ej. mensualidad)</label>
</div>
<div>
    <label>Porcentaje para socios suspendidos (%)</label>
    <input type="number" step="0.01" min="0" max="100" name="porcentaje_suspendido" value="{{ old('porcentaje_suspendido', $tipoCargo->porcentaje_suspendido ?? 25) }}" required>
    <small style="display:block; margin-top:-10px; margin-bottom:14px; color:#666;">
        Al aplicar este cargo a todos los socios, quienes esten suspendidos pagaran solo este porcentaje del monto.
    </small>
</div>
