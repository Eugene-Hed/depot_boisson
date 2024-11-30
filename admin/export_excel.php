<?php
require_once '../includes/database.php';
require_once '../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

// Get date range
$debut = $_GET['debut'] ?? date('Y-m-01');
$fin = $_GET['fin'] ?? date('Y-m-t');

// Fetch data
$sql = "SELECT p.nom, 
        COALESCE(SUM(dc.quantite), 0) as quantite_vendue, 
        COALESCE(SUM(dc.quantite * dc.prix_unit), 0) as total_ventes
        FROM produit p
        LEFT JOIN detailscommandeclient dc ON p.id_produit = dc.id_produit
        LEFT JOIN commandeclient cc ON dc.id_commande = cc.id_commande
        WHERE cc.date_commande BETWEEN :debut AND :fin
        GROUP BY p.id_produit, p.nom
        ORDER BY quantite_vendue DESC";
$stmt = $conn->prepare($sql);
$stmt->execute([':debut' => $debut, ':fin' => $fin]);
$data = $stmt->fetchAll(PDO::FETCH_ASSOC);

$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();

// Set headers
$sheet->setCellValue('A1', 'Rapport des Ventes');
$sheet->mergeCells('A1:C1');
$sheet->setCellValue('A3', 'Produit');
$sheet->setCellValue('B3', 'Quantité Vendue');
$sheet->setCellValue('C3', 'Total des Ventes');

// Add data
$row = 4;
foreach ($data as $item) {
    $sheet->setCellValue('A' . $row, $item['nom']);
    $sheet->setCellValue('B' . $row, $item['quantite_vendue']);
    $sheet->setCellValue('C' . $row, $item['total_ventes']);
    $row++;
}

// Style the worksheet
$sheet->getStyle('A1:C1')->getFont()->setBold(true);
$sheet->getStyle('A3:C3')->getFont()->setBold(true);
$sheet->getStyle('C4:C' . ($row-1))->getNumberFormat()->setFormatCode('#,##0 "FCFA"');

foreach(range('A','C') as $col) {
    $sheet->getColumnDimension($col)->setAutoSize(true);
}

header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="rapport_ventes.xlsx"');
header('Cache-Control: max-age=0');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
