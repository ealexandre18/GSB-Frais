<?php
namespace Pg\GsbFraisBundle\services;

use FPDF;

require_once __DIR__ . '/../Controller/include/fct.inc.php';

class PdfGsb extends FPDF
{
    private $nomMoisFr = [
        '01' => 'Janvier', '02' => 'Février', '03' => 'Mars', '04' => 'Avril',
        '05' => 'Mai', '06' => 'Juin', '07' => 'Juillet', '08' => 'Août',
        '09' => 'Septembre', '10' => 'Octobre', '11' => 'Novembre', '12' => 'Décembre'
    ];

    public function __construct()
    {
        parent::__construct('P', 'mm', 'A4');
        $this->SetAutoPageBreak(true, 15);
    }

    /**
     * Ajoute une page de fiche de remboursement conforme à l'Annexe 1
     */
    public function ajouterFicheRemboursement($visiteur, $mois, $lesFraisForfait, $lesFraisHorsForfait, $vehicule)
    {
        $this->AddPage();
        
        $numAnnee = substr($mois, 0, 4);
        $numMois = substr($mois, 4, 2);
        $libelleMois = ($this->nomMoisFr[$numMois] ?? $numMois) . ' ' . $numAnnee;

        // 1. Logo GSB
        $logoPath = __DIR__ . '/../../../../web/bundles/gsbfrais/images/logo.jpg';
        if (file_exists($logoPath)) {
            $this->Image($logoPath, 85, 10, 40);
            $this->Ln(28);
        } else {
            $this->SetFont('Arial', 'B', 22);
            $this->SetTextColor(31, 78, 121);
            $this->Cell(0, 15, 'GSB', 0, 1, 'C');
            $this->Ln(5);
        }

        // 2. Cadre Titre "REMBOURSEMENT DE FRAIS ENGAGES"
        $this->SetDrawColor(31, 78, 121); // Bleu GSB
        $this->SetLineWidth(0.6);
        $this->SetFont('Arial', 'B', 13);
        $this->SetTextColor(31, 78, 121);
        $this->Cell(0, 10, 'REMBOURSEMENT DE FRAIS ENGAGES', 1, 1, 'C');
        $this->Ln(6);

        // 3. Cadre Informations Visiteur & Mois
        $this->SetFont('Arial', '', 11);
        $this->SetTextColor(0, 0, 0);
        
        // Ligne Visiteur
        $this->SetFont('Arial', 'B', 10);
        $this->Cell(28, 7, 'Visiteur', 0, 0, 'L');
        $this->SetFont('Arial', '', 10);
        $this->Cell(35, 7, $visiteur['id'] ?? '', 0, 0, 'L');
        $this->Cell(0, 7, iconv('UTF-8', 'ISO-8859-1//TRANSLIT', ($visiteur['nom'] ?? '') . '  ' . ($visiteur['prenom'] ?? '')), 0, 1, 'L');

        // Ligne Mois
        $this->SetFont('Arial', 'B', 10);
        $this->Cell(28, 7, 'Mois', 0, 0, 'L');
        $this->SetFont('Arial', '', 10);
        $this->Cell(0, 7, iconv('UTF-8', 'ISO-8859-1//TRANSLIT', $libelleMois), 0, 1, 'L');
        $this->Ln(6);

        // 4. Tableau 1 : Frais Forfaitaires
        $this->SetDrawColor(31, 78, 121);
        $this->SetLineWidth(0.4);
        $this->SetFont('Arial', 'BI', 10);
        $this->SetTextColor(31, 78, 121);

        // En-tête tableau forfait
        $this->Cell(65, 8, 'Frais Forfaitaires', 1, 0, 'L');
        $this->Cell(35, 8, iconv('UTF-8', 'ISO-8859-1//TRANSLIT', 'Quantité'), 1, 0, 'R');
        $this->Cell(45, 8, 'Montant unitaire', 1, 0, 'R');
        $this->Cell(45, 8, 'Total', 1, 1, 'R');

        $this->SetFont('Arial', '', 10);
        $this->SetTextColor(0, 0, 0);
        $totalForfait = 0;

        foreach ($lesFraisForfait as $unFrais) {
            $idFrais = $unFrais['idfrais'];
            $libelle = $unFrais['libelle'];
            $qte = (float)($unFrais['quantite'] ?? 0);
            
            // Calcul montant unitaire
            $montantUnitaire = (float)($unFrais['montant'] ?? 0);
            if ($idFrais === 'KM' && !empty($vehicule['montant_km'])) {
                $montantUnitaire = (float)$vehicule['montant_km'];
                $libelle = 'Véhicule (' . ($vehicule['carburant'] ?? '') . ' ' . ($vehicule['puissance'] ?? '') . ')';
            }
            
            $ligneTotal = $qte * $montantUnitaire;
            $totalForfait += $ligneTotal;

            $this->Cell(65, 7, iconv('UTF-8', 'ISO-8859-1//TRANSLIT', $libelle), 1, 0, 'L');
            $this->Cell(35, 7, number_format($qte, 0, ',', ' '), 1, 0, 'R');
            $this->Cell(45, 7, number_format($montantUnitaire, 2, '.', ' '), 1, 0, 'R');
            $this->Cell(45, 7, number_format($ligneTotal, 2, '.', ' '), 1, 1, 'R');
        }

        $this->Ln(8);

        // 5. Tableau 2 : Autres Frais (Hors Forfait)
        $this->SetFont('Arial', 'BI', 11);
        $this->SetTextColor(31, 78, 121);
        $this->Cell(0, 7, 'Autres Frais', 0, 1, 'C');

        // En-tête tableau hors forfait
        $this->SetFont('Arial', 'BI', 10);
        $this->Cell(35, 8, 'Date', 1, 0, 'C');
        $this->Cell(110, 8, iconv('UTF-8', 'ISO-8859-1//TRANSLIT', 'Libellé'), 1, 0, 'L');
        $this->Cell(45, 8, 'Montant', 1, 1, 'R');

        $this->SetFont('Arial', '', 10);
        $this->SetTextColor(0, 0, 0);
        $totalHorsForfait = 0;

        if (!empty($lesFraisHorsForfait)) {
            foreach ($lesFraisHorsForfait as $unFraisHF) {
                $dateHF = $unFraisHF['date'] ?? '';
                $libelleHF = $unFraisHF['libelle'] ?? '';
                $montantHF = (float)($unFraisHF['montant'] ?? 0);
                $totalHorsForfait += $montantHF;

                $this->Cell(35, 7, $dateHF, 1, 0, 'C');
                $this->Cell(110, 7, iconv('UTF-8', 'ISO-8859-1//TRANSLIT', substr($libelleHF, 0, 60)), 1, 0, 'L');
                $this->Cell(45, 7, number_format($montantHF, 2, '.', ' '), 1, 1, 'R');
            }
        } else {
            $this->Cell(190, 7, iconv('UTF-8', 'ISO-8859-1//TRANSLIT', 'Aucun frais hors forfait'), 1, 1, 'C');
        }

        $this->Ln(6);

        // 6. Bloc Total Général Encadré
        $totalGeneral = $totalForfait + $totalHorsForfait;
        $this->SetFont('Arial', 'B', 10);
        $this->SetTextColor(31, 78, 121);
        
        // Décalage pour aligner à droite
        $this->Cell(100, 8, '', 0, 0);
        $this->Cell(45, 8, 'TOTAL ' . $numMois . '/' . $numAnnee, 1, 0, 'C');
        $this->SetTextColor(0, 0, 0);
        $this->Cell(45, 8, number_format($totalGeneral, 2, '.', ' ') . ' EUR', 1, 1, 'R');

        $this->Ln(15);

        // 7. Pied de page / Signature
        $this->SetFont('Arial', '', 10);
        $this->SetTextColor(0, 0, 0);
        $dateDuJour = date('j') . ' ' . ($this->nomMoisFr[date('m')] ?? date('m')) . ' ' . date('Y');
        
        $this->Cell(100, 5, '', 0, 0);
        $this->Cell(90, 5, iconv('UTF-8', 'ISO-8859-1//TRANSLIT', 'Fait à Paris, le ' . $dateDuJour), 0, 1, 'L');
        
        $this->Cell(100, 5, '', 0, 0);
        $this->Cell(90, 5, iconv('UTF-8', 'ISO-8859-1//TRANSLIT', "Vu l'agent comptable"), 0, 1, 'L');
        
        $this->Ln(5);
        $this->Cell(115, 5, '', 0, 0);
        $this->SetFont('Arial', 'I', 12);
        $this->SetTextColor(31, 78, 121);
        $this->Cell(75, 10, 'GSB - Service Comptabilite', 0, 1, 'L');
    }
}
