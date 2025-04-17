<?php

namespace App\Service;

use Fpdf\Fpdf;
use App\Entity\User;
use App\Entity\ElementsPiedDePage\PDF;
use App\Entity\ElementsPiedDePage\PaginationPaysage;
use App\Entity\Facture;
use DateTime;

class ImpressionFicheDeVenteService extends FPDF
{
    public function __construct(
        protected EntetePaysagePaginantion $entetePaysagePagination
        )
    {}

    /**
     * Fonction qui permet d'imprimer la fiche de vente d'une caissière
     *
     * @param integer $nombreCashDuJour
     * @param integer $montantCashDuJour
     * @param integer $nombrePrisEnChargeDuJour
     * @param integer $montantPrisEnChargeDuJour
     * @param integer $nombreCreditDuJour
     * @param integer $montantCreditDuJour
     * @param array $cashs
     * @param array $prisEnCharges
     * @param array $credits
     * @param User $user
     * @return PDF
     */
    public function impressionFiheDeVente(int $nombreCashDuJour, int $montantCashDuJour, 
    int $nombrePrisEnChargeDuJour, int $montantPrisEnChargeDuJour, int $nombreCreditDuJour, 
    int $montantCreditDuJour, array $cashs, array $prisEnCharges, array $credits, int $montantAvanceCashDuJour,  int $montantAvancePrisEnChargeDuJour, int $montantAvanceCreditDuJour, User $caissiere = null, DateTime $dateDebut = null, DateTime $dateFin = null, bool $periode = null): PaginationPaysage
    {
        $pdf = new PaginationPaysage();
        $pdf->addPage('L');

        $pdf = $this->entetePaysagePagination->entetePaysagePagination($pdf);

        $pdf->SetLeftMargin(10);

        $positionY = 50;
        // $pdf->Ln(5);
        $pdf->SetXY(15, $positionY);

        // $pdf->Image('../public/images/qrcode/'.$facture->getQrCode(), 10, 40, 500);
        // $pdf->Image('../public/images/qrcode/'.$facture->getQrCode(), 165, 67, 34, 34);
        // if ($caissiere) 
        // {
        //     if ($caissiere->getPhoto()) 
        //     {
        //         $pdf->Image('../public/images/photoUsers/'.$caissiere->getPhoto(), 250, 60, 25);
        //     } else 
        //     {
        //         $pdf->Image('../public/images/photoUsers/user.png', 250, 60, 25);
        //     }
        // }
        
        $pdf->SetFont('Arial', 'B', 12);

        if ($caissiere) 
        {
            // $pdf->Ln(15);
            $pdf->SetX(15);
            if ($caissiere) 
            {
                $pdf->Cell(0, 5, 'FICHE DE VENTE DE : '.$caissiere->getNom(), 0, 1, 'C', 0);
                $pdf->Cell(0, 5, 'Contact : '.$caissiere->getContact(), 0, 1, 'C', 0);
            } 
            else 
            {
                $pdf->Cell(0, 5, 'FICHE DE VENTE', 0, 1, 'C', 0);

                $pdf->SetX(15);
                $pdf->SetFont('Arial', 'B', 10);
            }
            
        } 
        else 
        {
            // $pdf->Ln(15);
            $pdf->SetX(15);
            $pdf->Cell(0, 5, 'FICHE DE VENTE', 0, 1, 'C', 0);

        }
        
        $pdf->SetX(15);
        $pdf->SetFont('Arial', 'B', 8);

        if ($periode) 
        {
            $pdf->Cell(0, 5, utf8_decode('Période allat du : ').date_format($dateDebut, 'd-m-Y').utf8_decode(' au ').date_format($dateFin, 'd-m-Y'), 0, 1, 'C', 0);
        } 
        else 
        {
            $pdf->Cell(0, 5, 'Date : '.date_format(new DateTime('now'), 'd-m-Y H:i:s'), 0, 1, 'C', 0);
        }
        
        $pdf->Ln(10);
        $pdf->SetX(15);
        $pdf->SetFont('Arial', 'B', 12);

        if (count($cashs) != 0) 
        {
            $pdf->Cell(0, 5, "FACTURES CASH", 0, 1, 'L', 0);

            // Entête du tableau
            $pdf = $this->enteteTableau($pdf);

            $pdf->SetFont('Arial', '', 10);
            $i = 1 ;
            foreach ($cashs as $cash) 
            {   
                $pdf = $this->contenuTableau($pdf, $i, $cash);

                $i++;
            }
            
            $pdf = $this->basTableau($pdf, $montantCashDuJour, $montantAvanceCashDuJour, ($montantCashDuJour-$montantAvanceCashDuJour));
        }

        $pdf->Ln(5);
        $pdf->SetX(15);
        $pdf->SetFont('Arial', 'B', 12);
        
        if (count($prisEnCharges) != 0) 
        {
            $pdf->Cell(0, 5, "FACTURES PRIS EN CHARGE", 0, 1, 'L', 0);

            // Entête du tableau
            $pdf = $this->enteteTableau($pdf);

            $pdf->SetFont('Arial', '', 10);
            $i = 1 ;
            foreach ($prisEnCharges as $prisEnCharge) 
            {   
                $pdf = $this->contenuTableau($pdf, $i, $prisEnCharge);
                $i++;
            }

            $pdf = $this->basTableau($pdf, $montantPrisEnChargeDuJour, $montantAvancePrisEnChargeDuJour, ($montantPrisEnChargeDuJour - $montantAvancePrisEnChargeDuJour));
        }

        $pdf->Ln(5);
        $pdf->SetX(15);
        $pdf->SetFont('Arial', 'B', 12);
        
        if (count($credits) != 0) 
        {
            $pdf->Cell(0, 5, "FACTURES CREDIT", 0, 1, 'L', 0);

            // Entête du tableau
            $pdf = $this->enteteTableau($pdf);

            $pdf->SetFont('Arial', '', 10);
            $i = 1 ;
            foreach ($credits as $credit) 
            {   
                $pdf = $this->contenuTableau($pdf, $i, $credit);
                $i++;
            }

            $pdf = $this->basTableau($pdf, $montantCreditDuJour, $montantAvanceCreditDuJour, ($montantCreditDuJour-$montantAvanceCreditDuJour));
        }

        $pdf->Ln(5);
        $pdf->SetX(15);
        $pdf->SetFont('Arial', 'B', 12);
        $pdf->Cell(0, 5, "RECAPITULATIF", 0, 1, 'C', 0);

        $pdf->SetX(15);
        $pdf->SetFillColor(240,240,240);
        $pdf->SetFont('Arial', 'B', 12);
        $pdf->Cell(60, 5, utf8_decode('FACTURES'), 1, 0, 'C', true);
        $pdf->Cell(30, 5, utf8_decode('NOMBRE'), 1, 0, 'C', true);
        $pdf->Cell(60, 5, utf8_decode('NET A PAYER (en FCFA)'), 1, 0, 'C', true);
        $pdf->Cell(60, 5, utf8_decode('AVANCE (en FCFA)'), 1, 0, 'C', true);
        $pdf->Cell(60, 5, utf8_decode('RESTE (en FCFA)'), 1, 1, 'C', true);

        $pdf->SetX(15);
        $pdf->Cell(60, 5, utf8_decode('CASH'), 1, 0, 'C', true);
        $pdf->SetFont('Arial', '', 10);
        $pdf->Cell(30, 5, utf8_decode(number_format($nombreCashDuJour, 0, '', ' ')), 1, 0, 'C');
        $pdf->Cell(60, 5, utf8_decode(number_format($montantCashDuJour, 0, '', ' ')), 1, 0, 'C');
        $pdf->Cell(60, 5, utf8_decode(number_format($montantAvanceCashDuJour, 0, '', ' ')), 1, 0, 'C');
        $pdf->Cell(60, 5, utf8_decode(number_format(($montantCashDuJour - $montantAvanceCashDuJour), 0, '', ' ')), 1, 1, 'C');

        $pdf->SetX(15);
        $pdf->SetFont('Arial', 'B', 12);
        $pdf->Cell(60, 5, utf8_decode('PRIS EN CHARGE'), 1, 0, 'C', true);
        $pdf->SetFont('Arial', '', 10);
        $pdf->Cell(30, 5, utf8_decode(number_format($nombrePrisEnChargeDuJour, 0, '', ' ')), 1, 0, 'C');
        $pdf->Cell(60, 5, utf8_decode(number_format($montantPrisEnChargeDuJour, 0, '', ' ')), 1, 0, 'C');
        $pdf->Cell(60, 5, utf8_decode(number_format($montantAvancePrisEnChargeDuJour, 0, '', ' ')), 1, 0, 'C');
        $pdf->Cell(60, 5, utf8_decode(number_format(($montantPrisEnChargeDuJour - $montantAvancePrisEnChargeDuJour), 0, '', ' ')), 1, 1, 'C');

        $pdf->SetX(15);
        $pdf->SetFont('Arial', 'B', 12);
        $pdf->Cell(60, 5, utf8_decode('CREDIT'), 1, 0, 'C', true);
        $pdf->SetFont('Arial', '', 10);
        $pdf->Cell(30, 5, utf8_decode(number_format($nombreCreditDuJour, 0, '', ' ')), 1, 0, 'C');
        // $pdf->Cell(60, 5, utf8_decode(number_format($montantCreditDuJour, 0, '', ' ')), 1, 0, 'C');
        $pdf->Cell(60, 5, utf8_decode(''), 1, 0, 'C');
        $pdf->Cell(60, 5, utf8_decode(number_format($montantAvanceCreditDuJour, 0, '', ' ')), 1, 0, 'C');
        $pdf->Cell(60, 5, utf8_decode(''), 1, 1, 'C');

        // $pdf->SetX(15);
        // $pdf->SetFont('Arial', 'B', 12);
        // $pdf->Cell(60, 5, utf8_decode('TOTAUX'), 1, 0, 'C', true);
        // $pdf->Cell(30, 5, utf8_decode(number_format(($nombreCashDuJour + $nombrePrisEnChargeDuJour + $nombreCreditDuJour), 0, '', ' ')), 1, 0, 'C', true);
        // $pdf->Cell(60, 5, utf8_decode(number_format(($montantCashDuJour + $montantPrisEnChargeDuJour + $montantCreditDuJour), 0, '', ' ')), 1, 0, 'C', true);
        // $pdf->Cell(60, 5, utf8_decode(number_format(($montantAvanceCashDuJour + $montantAvancePrisEnChargeDuJour + $montantAvanceCreditDuJour), 0, '', ' ')), 1, 0, 'C', true);
        // $pdf->Cell(60, 5, utf8_decode(number_format(($montantCashDuJour - $montantAvanceCashDuJour + $montantPrisEnChargeDuJour - $montantAvancePrisEnChargeDuJour + $montantCreditDuJour - $montantAvanceCreditDuJour), 0, '', ' ')), 1, 1, 'C', true);

        $pdf->Ln(10);
        $pdf->SetX(15);
        
        $pdf->SetFont('Arial', 'B', 12);
        $pdf->Cell(100, 5, utf8_decode('La Caissière'), 0, 0, 'C');
        $pdf->Cell(180, 5, utf8_decode("L'Administration"), 0, 0, 'C');

        return $pdf;
    }

    /**
     * Undocumented function
     *
     * @param PaginationPaysage $pdf
     * @return PaginationPaysage
     */
    public function enteteTableau(PaginationPaysage $pdf): PaginationPaysage
    {
        $pdf->SetX(15);
        $pdf->SetFillColor(240,240,240);
        $pdf->SetFont('Arial', 'B', 12);
        $pdf->Cell(20, 5, utf8_decode('N°'), 1, 0, 'C', true);
        $pdf->Cell(40, 5, utf8_decode('Ref. Facture'), 1, 0, 'C', true);
        $pdf->Cell(70, 5, utf8_decode('Client'), 1, 0, 'C', true);
        $pdf->Cell(50, 5, utf8_decode('Date'), 1, 0, 'C', true);
        $pdf->Cell(30, 5, utf8_decode('Nat à payer'), 1, 0, 'C', true);
        $pdf->Cell(30, 5, utf8_decode('Avance'), 1, 0, 'C', true);
        $pdf->Cell(30, 5, utf8_decode('Reste'), 1, 1, 'C', true);

        return $pdf;
    }

    /**
     * Undocumented function
     *
     * @param PaginationPaysage $pdf
     * @param integer $i
     * @param Facture $facture
     * @return PaginationPaysage
     */
    public function contenuTableau(PaginationPaysage $pdf, int $i, Facture $facture): PaginationPaysage
    {
        if ($i % 2 == 0) 
        {
            $pdf->SetFillColor(184,204,228);
        } 
        else 
        {
            $pdf->SetFillColor(255,255,255);
        }
                
        $pdf->SetX(15);
        $pdf->SetFont('Arial', '', 12);
        $pdf->Cell(20, 5, utf8_decode($i), 1, 0, 'C', true);
        $pdf->Cell(40, 5, utf8_decode($facture->getReference()), 1, 0, 'C', true);
        $pdf->Cell(70, 5, utf8_decode($facture->getPatient() ? $facture->getPatient()->getNom(): $facture->getNomPatient() ), 1, 0, 'C', true);
        $pdf->Cell(50, 5, utf8_decode(date_format($facture->getDateFactureAt(), 'd-m-Y').utf8_decode(' à ').date_format($facture->getHeure(), 'H:i:s')), 1, 0, 'C', true);
        $pdf->Cell(30, 5, utf8_decode(number_format($facture->getNetApayer(), 0, '', ' ')), 1, 0, 'C', true);
        $pdf->Cell(30, 5, utf8_decode(number_format($facture->getAvance(), 0, '', ' ')), 1, 0, 'C', true);
        $pdf->Cell(30, 5, utf8_decode(number_format(($facture->getReste()), 0, '', ' ')), 1, 1, 'C', true);

        return $pdf;
    }

    /**
     * Undocumented function
     *
     * @param PaginationPaysage $pdf
     * @param integer $montant
     * @param integer $avance
     * @param integer $reste
     * @return PaginationPaysage
     */
    public function basTableau(PaginationPaysage $pdf, int $montant, int $avance, int $reste): PaginationPaysage
    {
        $pdf->SetX(15);
        $pdf->SetFillColor(240,240,240);
        $pdf->SetFont('Arial', 'B', 12);
        $pdf->Cell(180, 5, utf8_decode('MONTANT'), 1, 0, 'C', true);
        $pdf->Cell(30, 5, utf8_decode(' '), 1, 0, 'C', true);
        $pdf->Cell(30, 5, utf8_decode(number_format($avance, 0, '', ' ')), 1, 0, 'C', true);
        $pdf->Cell(30, 5, utf8_decode(' '), 1, 1, 'C', true);

        return $pdf;
    }
}

