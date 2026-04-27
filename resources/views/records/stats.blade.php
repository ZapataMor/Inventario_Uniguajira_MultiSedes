<div class="flex items-center gap-4 mt-3 mb-6">
    <!-- Total registros -->
    <div class="flex flex-col items-center gap-1">
        <div class="flex items-center gap-2 px-3 py-2 rounded-lg bg-blue-50">
            <i class="fas fa-database text-blue-500 text-base"></i>
            <span class="text-sm font-bold text-gray-900">{{ number_format($logs->total()) }}</span>
        </div>
        <span class="text-xs text-gray-500 font-medium">Total registros</span>
    </div>

    <!-- Esta semana -->
    <div class="flex flex-col items-center gap-1">
        <div class="flex items-center gap-2 px-3 py-2 rounded-lg bg-orange-50">
            <i class="fas fa-calendar text-orange-500 text-base"></i>
            <span class="text-sm font-bold text-gray-900">{{ \App\Models\ActivityLog::whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])->count() }}</span>
        </div>
        <span class="text-xs text-gray-500 font-medium">Esta semana</span>
    </div>
    
    <!-- Acciones de hoy -->
    <div class="flex flex-col items-center gap-1">
        <div class="flex items-center gap-2 px-3 py-2 rounded-lg bg-green-50">
            <i class="fas fa-clock text-green-500 text-base"></i>
            <span class="text-sm font-bold text-gray-900">{{ \App\Models\ActivityLog::whereDate('created_at', today())->count() }}</span>
        </div>
        <span class="text-xs text-gray-500 font-medium">Acciones hoy</span>
    </div>
    
    <!-- Usuarios activos -->
    <div class="flex flex-col items-center gap-1">
        <div class="flex items-center gap-2 px-3 py-2 rounded-lg bg-purple-50">
            <i class="fas fa-users text-purple-500 text-base"></i>
            <span class="text-sm font-bold text-gray-900">{{ \App\Models\ActivityLog::distinct('user_id')->whereDate('created_at', today())->count('user_id') }}</span>
        </div>
        <span class="text-xs text-gray-500 font-medium">Usuarios activos</span>
    </div>
</div>
