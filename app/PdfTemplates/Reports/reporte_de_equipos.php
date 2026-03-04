<?php
// Incluir el autoloader de Composer
require_once __DIR__ . '/../../vendor/autoload.php';

// Si la clase no se encuentra automáticamente, incluirla directamente
require_once __DIR__ . '/../helpers/PdfGenerator.php';
require_once __DIR__ . '/../models/ReportsPDF.php';

// Usar el namespace correcto
use app\PdfGenerator;

/**
 * Clase para generar reportes PDF de todos los equipos con número de serie
 */
class SerialGoodsReportGenerator {
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
     * Obtiene todos los equipos con número de serie
     * 
     * @return array Lista de equipos con número de serie
     */
    public function getSerialGoods() {
        return $this->reportsPDF->getAllSerialGoods();
    }
    
    /**
     * Agrupa los equipos por tipo de bien
     * 
     * @param array $goods Lista de equipos
     * @return array Equipos agrupados por bien
     */
    private function groupGoodsByType($goods) {
        $groupedGoods = [];
        
        foreach ($goods as $good) {
            $bien = $good['bien'];
            if (!isset($groupedGoods[$bien])) {
                $groupedGoods[$bien] = [];
            }
            $groupedGoods[$bien][] = $good;
        }
        
        // Ordenar los grupos por nombre del bien
        ksort($groupedGoods);
        
        return $groupedGoods;
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
     * Genera el HTML para el reporte de equipos con número de serie
     * 
     * @return string HTML del reporte
     */
    public function generateSerialGoodsReportHtml() {
        $dataGoodsSerial = $this->getSerialGoods();
        $groupedGoods = $this->groupGoodsByType($dataGoodsSerial);
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
                <title>REPORTE DE TODOS LOS EQUIPOS</title>
                <style>
                    body {
                        font-family: Arial, sans-serif;
                        font-size: 12px;
                    }
                    table {
                        width: 100%;
                        border-collapse: collapse;
                        margin-bottom: 30px;
                        page-break-inside: avoid;
                    }
                    th, td {
                        border: 1px solid #ddd;
                        padding: 5px;
                        text-align: left;
                        font-size: 9px;
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
                        margin-bottom: 20px;
                    }
                    .header h1 {
                        margin: 0;
                        color: #333;
                        font-size: 16px;
                        margin-bottom: 10px;
                    }
                    .logo {
                        margin-bottom: 10px;
                    }
                    .footer {
                        text-align: center;
                        font-size: 10px;
                        margin-top: 30px;
                        color: #666;
                    }
                    .section-title {
                        background-color: #e8e8e8;
                        color: #333;
                        font-size: 14px;
                        font-weight: bold;
                        padding: 10px;
                        margin: 20px 0 10px 0;
                        border-left: 4px solid #4CAF50;
                        page-break-after: avoid;
                    }
                    .goods-count {
                        font-size: 12px;
                        color: #666;
                        font-weight: normal;
                        margin-left: 10px;
                    }
                    /* Evitar que las tablas se corten */
                    .table-section {
                        page-break-inside: avoid;
                    }
                    /* Si la tabla es muy larga, permitir que se divida */
                    .long-table {
                        page-break-inside: auto;
                    }
                </style>
            </head>
            <body>
                <div class="header">
                    <div class="logo">
                        ' . $logoHtml . '
                    </div>
                    <h1>REPORTE DE TODOS LOS EQUIPOS UNIGUAJIRA MAICAO</h1>
                    <p>Fecha de generación: ' . $date . '</p>
                </div>';

        // Generar una tabla para cada tipo de bien
        foreach ($groupedGoods as $tipoGien => $equipos) {
            $cantidadEquipos = count($equipos);
            $tableClass = $cantidadEquipos > 15 ? 'long-table' : 'table-section';
            
            $html .= '
                <div class="' . $tableClass . '">
                    <div class="section-title">
                        ' . htmlspecialchars($tipoGien) . '
                        <span class="goods-count">(' . $cantidadEquipos . ' equipo' . ($cantidadEquipos != 1 ? 's' : '') . ')</span>
                    </div>
                    
                    <table>
                        <thead>
                            <tr>
                                <th>Descripción</th>
                                <th>Marca</th>
                                <th>Modelo</th>
                                <th>Serial</th>
                                <th>Ubicación</th>
                                <th>Estado</th>
                                <th>Condición</th>
                            </tr>
                        </thead>
                        <tbody>';

            foreach ($equipos as $equipo) {
                $html .= '
                    <tr>
                        <td>' . htmlspecialchars($equipo['descripcion']) . '</td>
                        <td>' . htmlspecialchars($equipo['marca']) . '</td>
                        <td>' . htmlspecialchars($equipo['modelo']) . '</td>
                        <td>' . htmlspecialchars($equipo['serial']) . '</td>
                        <td>' . htmlspecialchars($equipo['nombre_inventario'] ?? 'No especificada') . '</td>
                        <td>' . htmlspecialchars($equipo['estado']) . '</td>
                        <td>' . htmlspecialchars($equipo['condiciones_tecnicas']) . '</td>
                    </tr>';
            }

            $html .= '
                        </tbody>
                    </table>
                </div>';
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
     * Genera y muestra el PDF del reporte de equipos con número de serie
     * 
     * @param string $filename Nombre del archivo PDF
     */
    public function generateAndStreamReport($filename = 'reporte_equipos.pdf') {
        $reportHtml = $this->generateSerialGoodsReportHtml();
        $this->pdfGenerator->generateAndStreamPdf($reportHtml, $filename);
    }
    
    /**
     * Genera y guarda el PDF del reporte de equipos con número de serie
     * 
     * @param string $outputPath Ruta donde guardar el archivo PDF
     * @return string Ruta completa donde se guardó el archivo
     */
    public function generateAndSaveReport($outputPath = null) {
        if (!$outputPath) {
            $outputPath = 'assets/storage/pdfs/reporte_equipos_' . date('Y-m-d_H-i-s') . '.pdf';
        }
        
        $reportHtml = $this->generateSerialGoodsReportHtml();
        $this->pdfGenerator->generateAndSavePdf($reportHtml, $outputPath);
        return $outputPath;
    }
}

// Script principal para generar el reporte (si se usa directamente este archivo)
if (basename(__FILE__) == basename($_SERVER['SCRIPT_FILENAME'])) {
    // Crear instancia del generador
    $reportGenerator = new SerialGoodsReportGenerator();
    
    // Generar y mostrar el PDF
    // $reportGenerator->generateAndStreamReport();
    
    // Alternativamente, para guardar el PDF en un archivo:
    $outputPath = $reportGenerator->generateAndSaveReport();
    // echo "PDF generado y guardado en: " . $outputPath;
}