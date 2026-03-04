<!-- < ?php require_once 'app/controllers/sessionCheck.php'; ?> -->

<div class="report-grid">
    <?php if (isset($dataIdFolder)): ?>
        <?php foreach ($dataIdFolder as $reports): ?>
            <div class="report-item-card card-item"
                 <?php if ($_SESSION['user_rol'] === 'administrador'): ?>
                    data-id="<?= htmlspecialchars($reports['id']) ?>"
                    data-name="<?= htmlspecialchars($reports['nombre']) ?>"
                    data-type="report"
                    onclick="toggleSelectItem(this)"
                <?php endif; ?>
            >
                
                <div class="report-folder-left">
                    <i class="fas fa-2x fa-file-pdf"></i>
                </div>
                
                <div class="report-folder-center">
                    <div id="inventory-name<?= htmlspecialchars($reports['id']) ?>"
                     class="title name-item"> <?= htmlspecialchars($reports['nombre']) ?> </div>
                    <div class="stats">
                        <span class="stat-item">
                            <i class="fas fa-calendar-alt"></i>
                            <?= htmlspecialchars($reports['fecha_creacion']) ?>
                        </span>
                    </div>
                </div>
                
                <div class="report-folder-right">
                    <button class="btn-open" onclick="event.stopPropagation(); downloadReport(<?= htmlspecialchars($reports['id']) ?>, '<?= htmlspecialchars($reports['nombre']) ?>')">
                        <i class="fas fa-external-link-alt"></i> Descargar
                    </button>
                </div>
                           
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <div class="empty-state">
            <i class="fas fa-folder-open fa-3x"></i>
            <p>No hay inventarios disponibles</p>
        </div>
    <?php endif; ?>
</div>
