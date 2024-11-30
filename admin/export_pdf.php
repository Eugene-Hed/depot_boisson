<?php
ob_start();
require_once '../includes/database.php';
require_once '../vendor/autoload.php';

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

// Create new PDF document
$pdf = new \TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

// Set document information
$pdf->SetCreator('Dépôt Manager');
$pdf->SetAuthor('Administrateur');
$pdf->SetTitle('Rapport des Ventes');

// Remove default header/footer
$pdf->setPrintHeader(false);
$pdf->setPrintFooter(false);

// Add a page
$pdf->AddPage();

// Set font
$pdf->SetFont('helvetica', 'B', 16);

// Title
$pdf->Cell(0, 15, 'Rapport des Ventes', 0, true, 'C');

// Table header
$pdf->SetFont('helvetica', '', 11);
$pdf->SetFillColor(240, 240, 240);
$pdf->Cell(80, 7, 'Produit', 1, 0, 'C', 1);
$pdf->Cell(50, 7, 'Quantité', 1, 0, 'C', 1);
$pdf->Cell(60, 7, 'Total', 1, 1, 'C', 1);

// Data rows
foreach ($data as $row) {
    $pdf->Cell(80, 6, $row['nom'], 1);
    $pdf->Cell(50, 6, number_format($row['quantite_vendue']), 1, 0, 'R');
    $pdf->Cell(60, 6, number_format($row['total_ventes']) . ' FCFA', 1, 1, 'R');
}

// Output the PDF
$pdf->Output('rapport_ventes.pdf', 'D');
