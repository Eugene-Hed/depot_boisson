<?php
ob_start();
require_once '../includes/database.php';
require_once '../vendor/autoload.php';

$commande_id = $_GET['id'] ?? null;

// Récupération des données
$sql = "SELECT cc.*, c.nom as client_nom, c.adresse as client_adresse, c.telephone as client_telephone 
        FROM commandeclient cc
        JOIN client c ON cc.id_client = c.id_client 
        WHERE cc.id_commande = :commande_id";
$stmt = $conn->prepare($sql);
$stmt->execute([':commande_id' => $commande_id]);
$commande = $stmt->fetch(PDO::FETCH_ASSOC);

$sql = "SELECT dc.*, p.nom AS nom_produit 
        FROM detailscommandeclient dc
        JOIN produit p ON dc.id_produit = p.id_produit
        WHERE dc.id_commande = :commande_id";
$stmt = $conn->prepare($sql);
$stmt->execute([':commande_id' => $commande_id]);
$details = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Create new PDF document
$pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

// Set document information
$pdf->SetCreator('Dépôt Manager');
$pdf->SetAuthor('Administrateur');
$pdf->SetTitle('Facture #' . $commande_id);

// Remove default header/footer
$pdf->setPrintHeader(false);
$pdf->setPrintFooter(false);

// Add a page
$pdf->AddPage();

// Set font
$pdf->SetFont('helvetica', 'B', 16);

// Title
$pdf->Cell(0, 15, 'Facture #' . $commande_id, 0, true, 'C');

// Client Info
$pdf->SetFont('helvetica', '', 11);
$pdf->Cell(80, 7, 'Client: ' . $commande['client_nom'], 0, true);
$pdf->Cell(80, 7, 'Adresse: ' . $commande['client_adresse'], 0, true);
$pdf->Cell(80, 7, 'Téléphone: ' . $commande['client_telephone'], 0, true);
$pdf->Ln(10);

// Table header
$pdf->SetFillColor(240, 240, 240);
$pdf->Cell(80, 7, 'Produit', 1, 0, 'C', 1);
$pdf->Cell(50, 7, 'Quantité', 1, 0, 'C', 1);
$pdf->Cell(60, 7, 'Total', 1, 1, 'C', 1);

// Data rows
foreach ($details as $row) {
    $pdf->Cell(80, 6, $row['nom_produit'], 1);
    $pdf->Cell(50, 6, number_format($row['quantite']), 1, 0, 'R');
    $pdf->Cell(60, 6, number_format($row['prix_unit'] * $row['quantite']) . ' FCFA', 1, 1, 'R');
}

// Output the PDF
$pdf->Output('facture_' . $commande_id . '.pdf', 'D');
