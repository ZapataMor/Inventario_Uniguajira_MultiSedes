<?php
// Incluir el autoloader de Composer
require_once __DIR__ . '/../../vendor/autoload.php';

// Si la clase no se encuentra automáticamente, incluirla directamente
require_once __DIR__ . '/../helpers/PdfGenerator.php';
require_once __DIR__ . '/../models/ReportsPDF.php';

// Usar el namespace correcto
use app\PdfGenerator;

/**
 * Clase para generar reportes PDF de inventarios por grupo
 */
class InventoryGroupReportGenerator {
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
     * Obtiene información del grupo
     * 
     * @param int $groupId ID del grupo
     * @return array Información del grupo
     */
    public function getGroupInfo($groupId) {
        return $this->reportsPDF->getInfoGroup($groupId);
    }
    
    /**
     * Obtiene todos los inventarios relacionados con un grupo
     * 
     * @param int $groupId ID del grupo
     * @return array Lista de inventarios del grupo
     */
    public function getInventoriesByGroup($groupId) {
        return $this->reportsPDF->getInventoriesByGroup($groupId);
    }
    
    /**
     * Obtiene los bienes de un inventario específico
     * 
     * @param int $inventoryId ID del inventario
     * @return array Lista de bienes del inventario
     */
    public function getInventoryGoods($inventoryId) {
        return $this->reportsPDF->getInventoryWithGoods($inventoryId);
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
     * Genera el HTML para el reporte completo de inventarios por grupo
     * 
     * @param int $groupId ID del grupo
     * @return string HTML del reporte
     */
    public function generateGroupInventoriesReportHtml($groupId) {
        // Obtener información del grupo
        $groupInfo = $this->getGroupInfo($groupId);
        
        // Obtener todos los inventarios del grupo
        $inventories = $this->getInventoriesByGroup($groupId);
        
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
                <title>REPORTE DE GRUPO - ' . htmlspecialchars($groupInfo['nombre']) . '</title>
                <style>
                    body {
                        font-family: Arial, sans-serif;
                        font-size: 12px;
                    }
                    table {
                        width: 100%;
                        border-collapse: collapse;
                        margin-bottom: 20px;
                    }
                    th, td {
                        border: 1px solid #ddd;
                        padding: 8px;
                        text-align: left;
                    }
                    th {
                        background-color: #f2f2f2;
                        font-weight: bold;
                    }
                    tr:nth-child(even) {
                        background-color: #f9f9f9;
                    }
                    .header {
                        text-align: center;
                        margin-bottom: 10px;
                    }
                    .header h1 {
                        margin: 0;
                        color: #333;
                    }
                    .info {
                        text-align: center;
                        margin-bottom: 20px;
                        color: #555;
                    }
                    .footer {
                        text-align: center;
                        font-size: 10px;
                        margin-top: 30px;
                        color: #666;
                    }
                    .inventory-section {
                        margin-top: 30px;
                        margin-bottom: 40px;
                        page-break-inside: avoid;
                    }
                    .inventory-title {
                        background-color: #eaeaea;
                        padding: 10px;
                        margin-bottom: 15px;
                        border-left: 5px solid #4a90e2;
                    }
                    .inventory-status {
                        margin: 10px 0;
                        font-style: italic;
                        color: #555;
                    }
                    .page-break {
                        page-break-after: always;
                    }
                    .logo {
                        text-align: center;
                        margin-bottom: 10px;
                    }
                </style>
            </head>
            <body>
                <div class="header">
                    <div class="logo">
                        ' . $logoHtml . '
                    </div>
                    <h1>REPORTE DE GRUPO: ' . htmlspecialchars($groupInfo['nombre']) . '</h1>
                    <p>Fecha de generación: ' . $date . '</p>
                </div>';
        
        // Para cada inventario del grupo
        foreach ($inventories as $index => $inventory) {
            // Obtener los bienes de este inventario
            $inventoryGoods = $this->getInventoryGoods($inventory['id']);
            
            $html .= '
                <div class="inventory-section">
                    <h2 class="inventory-title">Inventario: ' . htmlspecialchars($inventory['nombre']) . '</h2>
                    <div class="inventory-status">
                        <p><strong>Estado de conservación:</strong> ' . htmlspecialchars($inventory['estado_conservacion']) . '</p>
                    </div>
                    
                    <table>
                        <thead>
                            <tr>
                                <th>Bien</th>
                                <th>Tipo</th>
                                <th>Cantidad</th>
                            </tr>
                        </thead>
                        <tbody>';
            
            // Si no hay bienes, mostrar mensaje
            if (empty($inventoryGoods)) {
                $html .= '
                            <tr>
                                <td colspan="3" style="text-align: center;">No hay bienes registrados en este inventario</td>
                            </tr>';
            } else {
                // Listar todos los bienes de este inventario
                foreach ($inventoryGoods as $good) {
                    $html .= '
                            <tr>
                                <td>' . htmlspecialchars($good['bien']) . '</td>
                                <td>' . htmlspecialchars($good['tipo']) . '</td>
                                <td>' . htmlspecialchars($good['cantidad']) . '</td>
                            </tr>';
                }
            }
            
            $html .= '
                        </tbody>
                    </table>
                </div>';
            
            // Añadir separador visual entre inventarios
            if ($index < count($inventories) - 1) {
                $html .= '<hr style="border: none; border-top: 2px solid #4a90e2; margin: 30px 0; opacity: 0.3;">';
            }
        }
        
        $html .= '
                <div class="footer">
                    <p>Este documento es un reporte generado automáticamente por el sistema de Inventario Uniguajira sede Maicao.</p>
                </div>
            </body>
        </html>';
        
        return $html;
    }
    
    /**
     * Genera y muestra el PDF del reporte de inventarios por grupo
     * 
     * @param int $groupId ID del grupo
     * @param string $filename Nombre del archivo PDF
     */
    public function generateAndStreamGroupReport($groupId, $filename = null) {
        $groupInfo = $this->getGroupInfo($groupId);
        if (!$filename) {
            $filename = 'reporte_grupo_' . preg_replace('/[^a-z0-9]/i', '_', $groupInfo['nombre']) . '.pdf';
        }
        
        $reportHtml = $this->generateGroupInventoriesReportHtml($groupId);
        $this->pdfGenerator->generateAndStreamPdf($reportHtml, $filename);
    }
    
    /**
     * Genera y guarda el PDF del reporte de inventarios por grupo
     * 
     * @param int $groupId ID del grupo
     * @param string $outputPath Ruta donde guardar el archivo PDF
     */
    public function generateAndSaveGroupReport($groupId, $outputPath = null) {
        if (!$outputPath) {
            $groupInfo = $this->getGroupInfo($groupId);
            $safeGroupName = preg_replace('/[^a-z0-9]/i', '_', $groupInfo['nombre']);
            $outputPath = 'assets/storage/pdfs/reporte_grupo_' . $safeGroupName . '_' . date('Y-m-d_H-i-s') . '.pdf';
        }
        
        $reportHtml = $this->generateGroupInventoriesReportHtml($groupId);
        $this->pdfGenerator->generateAndSavePdf($reportHtml, $outputPath);
        return $outputPath;
    }
}

// Script principal para generar el reporte
if (basename(__FILE__) == basename($_SERVER['SCRIPT_FILENAME'])) {
    // Verificar si se ha recibido el ID de grupo como parámetro
    $groupId = isset($_GET['grupo_id']) ? (int)$_GET['grupo_id'] : 1; // Valor predeterminado: 1

    // Inicializar el generador de reportes
    $reportGenerator = new InventoryGroupReportGenerator();

    // Generar y mostrar el PDF
    // $reportGenerator->generateAndStreamGroupReport($groupId);

    // Alternativamente, para guardar el PDF en un archivo:
    $outputPath = $reportGenerator->generateAndSaveGroupReport($groupId);
    // echo "PDF generado y guardado en: " . $outputPath;
}