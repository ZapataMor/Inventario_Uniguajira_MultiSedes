<?php
// Incluir el autoloader de Composer
require_once __DIR__ . '/../../vendor/autoload.php';

// Si la clase no se encuentra automáticamente, incluirla directamente
require_once __DIR__ . '/../helpers/PdfGenerator.php';
require_once __DIR__ . '/../models/ReportsPDF.php';

// Usar el namespace correcto
use app\PdfGenerator;

/**
 * Clase para generar reportes PDF de todos los inventarios de todos los grupos
 */
class AllGroupsInventoryReportGenerator {
    private $reportsPDF;
    private $pdfGenerator;
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->reportsPDF = new ReportsPDF();
        $this->pdfGenerator = new PdfGenerator();
    }
    
    /**
     * Obtiene información de todos los grupos
     * 
     * @return array Lista de todos los grupos
     */
    public function getAllGroups() {
        return $this->reportsPDF->getAllGroups();
    }
    
    /**
     * Convierte imagen a base64 para usar en PDF
     * 
     * @param string $imagePath Ruta de la imagen
     * @return string|null Imagen en base64 o null si no existe
     */
    private function getImageBase64($imagePath) {
        // Intentar diferentes rutas posibles
        $possiblePaths = [
            __DIR__ . '/../../assets/images/logoUniguajira.png',
            __DIR__ . '/../../../assets/images/logoUniguajira.png',
            $_SERVER['DOCUMENT_ROOT'] . '/Inventario-Uniguajira/assets/images/logoUniguajira.png',
            realpath(__DIR__ . '/../../') . '/assets/images/logoUniguajira.png'
        ];
        
        foreach ($possiblePaths as $path) {
            if (file_exists($path)) {
                $imageData = file_get_contents($path);
                $imageType = pathinfo($path, PATHINFO_EXTENSION);
                return 'data:image/' . $imageType . ';base64,' . base64_encode($imageData);
            }
        }
        
        return null;
    }
    
    /**
     * Genera el HTML para el reporte completo de todos los grupos y sus inventarios (solo vista simple)
     * 
     * @return string HTML del reporte
     */
    public function generateAllGroupsInventoriesReportHtml() {
        // Obtener todos los grupos
        $allGroups = $this->getAllGroups();
        date_default_timezone_set('America/Bogota');
        $date = date('d/m/Y');
        
        // Obtener imagen en base64
        $logoBase64 = $this->getImageBase64('logoUniguajira.png');
        
        // Crear el HTML del logo
        $logoHtml = '';
        if ($logoBase64) {
            $logoHtml = '<img src="' . $logoBase64 . '" width="500" alt="Logo Uniguajira">';
        } else {
            // Fallback si no se encuentra la imagen
            $logoHtml = '<div style="width: 300px; height: 100px; border: 2px solid #333; display: flex; align-items: center; justify-content: center; margin: 0 auto;">
                            <span style="color: #333; font-weight: bold;">UNIGUAJIRA MAICAO</span>
                        </div>';
        }
        
        $html = '
        <!DOCTYPE html>
        <html>
            <head>
                <meta charset="UTF-8">
                <title>REPORTE GENERAL DE INVENTARIOS</title>
                <style>
                    body { font-family: Arial, sans-serif; font-size: 12px; margin: 20px; }
                    table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
                    th, td { border: 1px solid #ddd; padding: 8px; text-align: left; font-size: 11px; }
                    th { background-color: #f2f2f2; font-weight: bold; }
                    tr:nth-child(even) { background-color: #f9f9f9; }
                    .header { text-align: center; margin-bottom: 20px; border-bottom: 2px solid #333; padding-bottom: 10px; }
                    .header h1 { margin: 0; color: #333; font-size: 18px; }
                    .header p { margin: 5px 0 0 0; font-size: 12px; }
                    .footer { text-align: center; font-size: 10px; margin-top: 30px; color: #666; border-top: 1px solid #ddd; padding-top: 10px; }
                    .group-section { margin-top: 40px; margin-bottom: 20px; }
                    .group-title { background-color: #4a90e2; color: white; padding: 10px; margin-bottom: 15px; font-size: 16px; border-radius: 4px; }
                    .inventory-section { margin-top: 20px; margin-bottom: 30px; page-break-inside: avoid; }
                    .inventory-title { background-color: #eaeaea; padding: 8px; margin-bottom: 10px; border-left: 5px solid #4a90e2; font-size: 14px; }
                    .inventory-status { margin: 5px 0 10px 0; font-style: italic; color: #555; font-size: 11px; }
                    .page-break { page-break-after: always; }
                    .summary { background-color: #f8f8f8; padding: 10px; margin-bottom: 30px; border: 1px solid #ddd; border-radius: 4px; }
                    .summary h2 { font-size: 14px; margin-top: 0; margin-bottom: 10px; color: #333; }
                    .summary-table { width: 100%; border-collapse: collapse; }
                    .summary-table th { background-color: #e0e0e0; }
                    .no-items { text-align: center; padding: 20px; color: #777; font-style: italic; }
                    .text-center { text-align: center; }
                    .text-right { text-align: right; }
                    .page-number { position: absolute; bottom: 10px; width: 100%; text-align: center; font-size: 10px; color: #777; }
                    .logo { text-align: center; margin-bottom: 10px; }
                </style>
            </head>
            <body>
                <div class="header">
                    <div class="logo">
                        ' . $logoHtml . '
                    </div>
                    <h1>REPORTE GENERAL DE INVENTARIOS</h1>
                    <p>Fecha de generación: ' . $date . '</p>
                </div>
                <div class="summary">
                    <h2>Resumen General</h2>
                    <table class="summary-table">
                        <tr>
                            <th>Total Grupos</th>
                            <th>Total Inventarios</th>
                            <th>Total Bienes</th>
                        </tr>
                        <tr>
                            <td class="text-center">' . count($allGroups) . '</td>
                            <td class="text-center" id="total-inventarios">Calculando...</td>
                            <td class="text-center" id="total-bienes">Calculando...</td>
                        </tr>
                    </table>
                </div>';
        $totalInventarios = 0;
        $totalBienes = 0;
        foreach ($allGroups as $groupIndex => $group) {
            $inventories = $this->reportsPDF->getInventoriesByGroup($group['id']);
            $totalInventarios += count($inventories);
            $html .= '
                <div class="group-section">
                    <h2 class="group-title">GRUPO: ' . htmlspecialchars($group['nombre']) . '</h2>';
            if (empty($inventories)) {
                $html .= '
                    <div class="no-items">
                        <p>No hay inventarios registrados en este grupo</p>
                    </div>';
            } else {
                foreach ($inventories as $inventoryIndex => $inventory) {
                    $inventoryGoods = $this->reportsPDF->getInventoryWithGoods($inventory['id']);
                    $totalBienes += count($inventoryGoods);
                    $html .= '
                    <div class="inventory-section">
                        <h3 class="inventory-title">Inventario: ' . htmlspecialchars($inventory['nombre']) . '</h3>
                        <div class="inventory-status">
                            <p><strong>Estado de conservación:</strong> ' . htmlspecialchars($inventory['estado_conservacion']) . '</p>
                            <p><strong>Última modificación:</strong> ' . htmlspecialchars($inventory['fecha_modificacion']) . '</p>
                        </div>';
                    $html .= '
                        <table>
                            <thead>
                                <tr>
                                    <th>Bien</th>
                                    <th>Tipo</th>
                                    <th>Cantidad</th>
                                </tr>
                            </thead>
                            <tbody>';
                    if (empty($inventoryGoods)) {
                        $html .= '
                                <tr>
                                    <td colspan="3" class="text-center">No hay bienes registrados en este inventario</td>
                                </tr>';
                    } else {
                        foreach ($inventoryGoods as $good) {
                            $html .= '
                                <tr>
                                    <td>' . htmlspecialchars($good['bien']) . '</td>
                                    <td>' . htmlspecialchars($good['tipo']) . '</td>
                                    <td class="text-center">' . htmlspecialchars($good['cantidad']) . '</td>
                                </tr>';
                        }
                    }
                    $html .= '
                            </tbody>
                        </table>
                    </div>';
                    if ($inventoryIndex < count($inventories) - 1) {
                        $html .= '<hr style="border: none; border-top: 1px dashed #ccc; margin: 20px 0;">';
                    }
                }
            }
            $html .= '</div>';
            if ($groupIndex < count($allGroups) - 1) {
                $html .= '<div class="page-break"></div>';
            }
        }
        $html = str_replace('id="total-inventarios">Calculando...', 'id="total-inventarios">' . $totalInventarios, $html);
        $html = str_replace('id="total-bienes">Calculando...', 'id="total-bienes">' . $totalBienes, $html);
        $html .= '
                <div class="footer">
                    <p>Este documento es un reporte generado automáticamente por el sistema de Inventario Uniguajira sede Maicao.</p>
                </div>
                <div class="page-number">Página <span class="pagenum"></span></div>
            </body>
        </html>';
        return $html;
    }

    /**
     * Genera y muestra el PDF del reporte de todos los grupos e inventarios (solo vista simple)
     * 
     * @param string $filename Nombre del archivo PDF
     */
    public function generateAndStreamAllGroupsReport($filename = null) {
        if (!$filename) {
            $filename = 'reporte_general_inventarios_' . date('Y-m-d') . '.pdf';
        }
        $reportHtml = $this->generateAllGroupsInventoriesReportHtml();
        $this->pdfGenerator->generateAndStreamPdf($reportHtml, $filename);
    }

    /**
     * Genera y guarda el PDF del reporte de todos los grupos e inventarios (solo vista simple)
     * 
     * @param string $outputPath Ruta donde guardar el archivo PDF
     * @return string Ruta completa donde se guardó el archivo
     */
    public function generateAndSaveAllGroupsReport($outputPath = null) {
        if (!$outputPath) {
            $outputPath = 'assets/storage/pdfs/reporte_general_inventarios_' . date('Y-m-d_H-i-s') . '.pdf';
        }
        $reportHtml = $this->generateAllGroupsInventoriesReportHtml();
        $this->pdfGenerator->generateAndSavePdf($reportHtml, $outputPath);
        return $outputPath;
    }
}

// Script principal para generar el reporte
if (basename(__FILE__) == basename($_SERVER['SCRIPT_FILENAME'])) {
    // Inicializar el generador de reportes
    $reportGenerator = new AllGroupsInventoryReportGenerator();

    // Generar y mostrar el PDF (solo vista simple)
    // $reportGenerator->generateAndStreamAllGroupsReport();
    // Alternativamente, para guardar el PDF en un archivo:
    $outputPath = $reportGenerator->generateAndSaveAllGroupsReport();
    // echo "PDF generado y guardado en: " . $outputPath;
}