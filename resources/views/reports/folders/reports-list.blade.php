@if($reports->isEmpty())
    <div class="report-empty-state">
        <i class="fas fa-file-pdf fa-3x"></i>
        <p>No hay reportes en esta carpeta</p>
    </div>
@else
    @foreach($reports as $report)
        <div
            class="report-item-card card-item"
            @if(Auth::user()->isAdministrator())
                data-id="{{ $report->id }}"
                data-name="{{ $report->name }}"
                data-type="report"
                onclick="toggleSelectItem(this)"
            @endif
        >
            <div class="report-folder-left">
                @if(str_ends_with($report->path ?? '', '.xlsx'))
                    <i class="fas fa-file-excel fa-2x report-folder-icon" style="color:#1d6f42"></i>
                @else
                    <i class="fas fa-file-pdf fa-2x report-folder-icon"></i>
                @endif
            </div>

            <div class="report-folder-center">
                <div class="report-folder-name name-item">
                    {{ $report->name }}
                </div>
                <div class="report-folder-stats">
                    <span class="report-stat-item">
                        <i class="fas fa-calendar-alt"></i>
                        {{ $report->created_at->format('Y-m-d H:i') }}
                    </span>
                </div>
            </div>

            <div class="report-folder-right">
                <button
                    class="btn-open"
                    onclick='downloadReport(event, {{ $report->id }}, @json($report->name), "{{ str_ends_with($report->path ?? '', '.xlsx') ? 'xlsx' : 'pdf' }}")'
                >
                    <i class="fas fa-download"></i> Descargar
                </button>
            </div>
        </div>
    @endforeach
@endif
