<?php

namespace App\Service;

use App\Entity\ConstantsClass;
use Fpdf\Fpdf;
use App\Service\EntetePaysage;
use App\Service\EntetePortrait;
use App\Entity\ElementsPiedDePage\PDF;

class ImpressionFactureService extends FPDF
{
    public function __construct(
        protected EntetePaysage $entetePaysage, 
        protected EntetePortraitFacture $entetePortraitFacture,
        )
    {}

    public function impressionFacture($facture, $detailsFacture): PDF
    {
        $pdf = new PDF();
        $pdf->addPage('P');

        $pdf = $this->entetePortraitFacture->entetePortraitFacture($pdf);

        $pdf->SetLeftMargin(10);

        $positionY = 50;
        $pdf->SetXY(15, $positionY);

        // $pdf->Image('../public/images/qrcode/'.$facture->getQrCode(), 10, 40, 500);
        // $pdf->Image('../public/images/qrcode/'.$facture->getQrCode(), 165, 67, 34, 34);
        
        $pdf->Ln(-8);
        $pdf->SetFont('Arial', 'B', 12);
        $pdf->SetX(15);
        $pdf->Cell(100, 5, 'DETAILS DE LA FACTURE : '.$facture->getReference(), 0, 0, 'L', 0);

        $pdf->SetFont('Arial', 'B', 8);
        $pdf->Cell(80.5, 5, 'Date de la facture : '.date_format($facture->getDateFactureAt(), 'd/m/Y').utf8_decode(' à ').date_format($facture->getHeure(), 'H:i:s'), 0, 1, 'R', 0);
        $pdf->SetFont('Arial', 'I', 10);
        $pdf->SetX(15);
        $pdf->Cell(50, 5, 'DETAILS OF ORDER', 0, 0, 'L', 0);

        $pdf->SetFont('Arial', 'B', 8);
        $pdf->Cell(87, 5, 'PAR : ', 0, 0, 'R', 0);
        $pdf->Cell(50, 5, $facture->getCaissiere() ? utf8_decode($facture->getCaissiere()->getNom()) : "CAISSIERE", 0, 1, 'L', 0);

        $pdf->Ln(5);
        $pdf->SetX(15);
        $pdf->SetFont('Arial', '', 10);

        if ($facture->getPatient()) 
        {
            $pdf->SetX(15);
            $pdf->Cell(120, 5, utf8_decode("Nom du client :".$facture->getPatient()->getNom()), 0, 0, 'L', 0);
            $pdf->SetFont('Arial', 'B', 10);

            if ($facture->getPrescripteur() == null) 
            {
                $pdf->Cell(62, 5, utf8_decode("Prescrit par : // "), 0, 1, 'R', 0);
            }
            else 
            {
                $pdf->Cell(62, 5, utf8_decode("Prescrit par :  ".$facture->getPrescripteur()->getPrescripteur()), 0, 1, 'R', 0);
            }
            
            
            $pdf->SetX(15);
            $pdf->Cell(0, 5, utf8_decode("Date de naissance : ".date_format($facture->getPatient()->getDateNaissanceAt(), 'd-m-Y')), 0, 1, 'L', 0);

            $pdf->SetX(15);
            $pdf->Cell(0, 5, utf8_decode("Ville : ".$facture->getPatient()->getVilleResidence()), 0, 1, 'L', 0);

            $pdf->SetX(15);
            $pdf->Cell(0, 5, utf8_decode("Pays : ".$facture->getPatient()->getPays()->getPays()), 0, 1, 'L', 0);

            $pdf->SetX(15);
            $pdf->Cell(0, 5, utf8_decode("Téléphone : ".$facture->getContactPatient()), 0, 1, 'L', 0);
        } 
        else 
        {
            $pdf->SetX(15);
            $pdf->Cell(120, 5, utf8_decode("Nom du client :  ".$facture->getNomPatient()), 0, 0, 'L', 0);
            
            $pdf->SetFont('Arial', 'B', 10);
            $pdf->Cell(62, 5, utf8_decode("Prescrit par :  ".$facture->getPrescripteur()->getPrescripteur()), 0, 1, 'R', 0);

            $pdf->SetX(15);
            $pdf->SetFont('Arial', '', 10);
            $pdf->Cell(20, 5, utf8_decode("Téléphone : "), 0, 0, 'L', 0);
            $pdf->Cell(100, 5, utf8_decode($facture->getContactPatient() ? $facture->getContactPatient() : $facture->getPatient()->getTelephone()), 0, 1, 'L', 0);

        }
        
        
        
        $pdf->SetFont('Arial', '', 10);
        $pdf->SetX(15);
        $pdf->Cell(40, 5, utf8_decode("Etat de la facture : "), 0, 0, 'L', 0);
        $pdf->SetFont('Arial', 'B', 10);
        $pdf->Cell(0, 5, utf8_decode($facture->getEtatFacture() ? $facture->getEtatFacture()->getEtatFacture() : ""), 0, 1, 'L', 0);

        $pdf->SetX(15);
        $pdf->SetFont('Arial', '', 10);
        $pdf->Cell(45, 5, utf8_decode("Mode de paiement choisi : "), 0, 0, 'L', 0);
        $pdf->SetFont('Arial', 'B', 10);
        $pdf->Cell(0, 5, utf8_decode($facture->getModePaiement() ? $facture->getModePaiement()->getModePaiement() : ""), 0, 1, 'L', 0);

        $pdf->SetX(15);
        $pdf->Cell(75, 5, utf8_decode("Cette facture s'élève à un montant de : "), 0, 0, 'L', 0);
        $pdf->SetFont('Arial', 'B', 10);
        $pdf->Cell(0, 5, utf8_decode(number_format($facture->getNetApayer(), 0, '', ' ')." FCFA"), 0, 1, 'L', 0);

        $positionY = 80;
        $pdf->SetFont('Arial', 'B', 10);
        $pdf->SetX(15);
        
        $pdf->Cell(0, 10, utf8_decode('Eléménts de la facture'), 0, 1, 'L', 0);

        $pdf->SetX(15);
        $pdf->SetFillColor(240,240,240);
        $pdf->Cell(7, 5, utf8_decode('N°'), 1, 0, 'C', true);
        $pdf->Cell(90, 5, utf8_decode('Produits'), 1, 0, 'C', true);
        $pdf->Cell(25, 5, utf8_decode('Pu (FCFA)'), 1, 0, 'C', true);
        $pdf->Cell(20, 5, utf8_decode('Qté'), 1, 0, 'C', true);
        $pdf->Cell(40, 5, utf8_decode('Total (FCFA)'), 1, 1, 'C', true);

        
        $i = 1;
        foreach ($detailsFacture as $detailsFactur) 
        {
            if ($i % 2 == 0) 
            {
                $pdf->SetFillColor(202,219,255);
            }else {
                $pdf->SetFillColor(255,255,255);
            }
            $pdf->SetX(15);
            $pdf->SetFont('Arial', '', 8);
                if ($detailsFactur->getProduit()->isKit() == 1) 
                {
                    $pdf->Cell(7, 5, utf8_decode($i), "LTR", 0, 'C', true);
                    $pdf->Cell(175, 5, utf8_decode($detailsFactur->getProduit()->getLibelle()), 1, 1, 'L', true);
                    $pdf->SetX(15);
                    $pdf->SetFont('Arial', 'B', 7);
                    $pdf->SetFillColor(240,240,240);
                    $pdf->Cell(7, 5, utf8_decode(""), "LR", 0, 'C');
                    $pdf->Cell(7, 5, utf8_decode('N°'), 1, 0, 'C', true);
                    $pdf->Cell(83, 5, utf8_decode('Eléments du kit'), 1, 0, 'C', true);
                    $pdf->Cell(25, 5, utf8_decode('Pu (FCFA)'), 1, 0, 'C', true);
                    $pdf->Cell(20, 5, utf8_decode('Qté'), 1, 0, 'C', true);
                    $pdf->Cell(40, 5, utf8_decode('Total (FCFA)'), 1, 1, 'C', true);

                    $p = 1;
                    $sousTotal = 0;
                    $pdf->SetFont('Arial', '', 7);
                    foreach ($detailsFactur->getProduit()->getProduitLigneDeKits() as $ligneDeKit) 
                    {
                        if ($p % 2 == 0) 
                        {
                            $pdf->SetFillColor(202,219,255);
                        }else {
                            $pdf->SetFillColor(255,255,255);
                        }

                        $pdf->SetX(15);
                        $pdf->Cell(7, 5, utf8_decode(""), "LR", 0, 'C');
                        $pdf->Cell(7, 5, utf8_decode($p), 1, 0, 'C', true);
                        $pdf->Cell(83, 5, utf8_decode($ligneDeKit->getProduit()->getLibelle() ? $ligneDeKit->getProduit()->getLibelle() : $ligneDeKit->getProduit()->getLibelle()), 1, 0, 'L', true);
                        $pdf->Cell(25, 5, utf8_decode(number_format($ligneDeKit->getPrix(), 0, '', ' ')), 1, 0, 'C', true);
                        $pdf->Cell(20, 5, utf8_decode(number_format($ligneDeKit->getQuantite(), 0, '', ' ')), 1, 0, 'C', true);
                        $pdf->Cell(40, 5, utf8_decode(number_format($ligneDeKit->getTotal(), 0, '', ' ')), 1, 1, 'C', true);

                        $sousTotal += $ligneDeKit->getTotal();

                        $p = $p + 1;
                    }

                    
                    $pdf->SetX(15);
                    
                    $pdf->SetFont('Arial', 'B', 7);
                    $pdf->SetFillColor(240,240,240);
                    $pdf->Cell(7, 5, utf8_decode(""), "LBT", 0, 'C', true);
                    $pdf->Cell(135, 5, utf8_decode('Sous Total du kit'), "TBR", 0, 'C', true);
                    $pdf->Cell(40, 5, utf8_decode(number_format($sousTotal, 0, '', ' ')." FCFA"), 1, 1, 'C', true);

                }
                else
                {
                    $pdf->SetFont('Arial', '', 8);
                    $pdf->Cell(7, 5, utf8_decode($i), 1, 0, 'C', true);
                    $pdf->Cell(90, 5, utf8_decode($detailsFactur->getProduit()->getLibelle()), 1, 0, 'L', true);
                    $pdf->Cell(25, 5, utf8_decode(number_format($detailsFactur->getPrix(), 0, '', ' ')), 1, 0, 'C', true);
                    $pdf->Cell(20, 5, utf8_decode(number_format($detailsFactur->getQuantite(), 0, '', ' ')), 1, 0, 'C', true);
                    $pdf->Cell(40, 5, utf8_decode(number_format($detailsFactur->getPrixQuantite(), 0, '', ' ')), 1, 1, 'C', true);
                }


            $i++;
            
        }

        //$pdf->SetX(15);
        //$pdf->SetFont('Arial', 'B', 10);
        //$pdf->Cell(142, 5, utf8_decode('Montant HT'), 0, 0, 'R');
        //$pdf->Cell(40, 5, utf8_decode(number_format($facture->getNetApayer(), 0, '', ' ')), 1, 1, 'C');

        //$pdf->SetX(15);
        //$pdf->Cell(142, 5, utf8_decode('TVA'), 0, 0, 'R');
        //$pdf->Cell(40, 5, utf8_decode("0%"), 1, 1, 'C');

        //$pdf->SetX(15);
        //$pdf->Cell(142, 5, utf8_decode('Montant TTC'), 0, 0, 'R');
        //$pdf->Cell(40, 5, utf8_decode(number_format($facture->getNetApayer(), 0, '', ' ')), 1, 1, 'C');

        $pdf->SetFont('Arial', 'B', 12);
        $pdf->SetX(15);
        $pdf->SetFillColor(202, 219, 255);
        $pdf->Cell(142, 5, utf8_decode('NET A PAYER'), 0, 0, 'R');
        $pdf->Cell(40, 5, utf8_decode(number_format($facture->getNetApayer(), 0, '', ' ')." FCFA"), 1, 1, 'C', true);

        if ($facture->getModePaiement()->getModePaiement() == ConstantsClass::PRIS_EN_CHARGE ) 
        {

            $pdf->SetX(15);
            $pdf->SetFillColor(202, 219, 255);
            $pdf->Cell(142, 5, utf8_decode('Avance du jour'), 0, 0, 'R');
            $pdf->Cell(40, 5, utf8_decode("00 FCFA"), 1, 1, 'C', true);

            $pdf->SetX(15);
            $pdf->SetFillColor(202, 219, 255);
            $pdf->Cell(142, 5, utf8_decode('Total avance'), 0, 0, 'R');
            $pdf->Cell(40, 5, utf8_decode("00 FCFA"), 1, 1, 'C', true);
            
            $pdf->SetX(15);
            $pdf->SetFillColor(202, 219, 255);
            $pdf->Cell(142, 5, utf8_decode('Reste à payer'), 0, 0, 'R');
            $pdf->Cell(40, 5, utf8_decode(number_format($facture->getNetApayer(), 0, '', ' ')." FCFA"), 1, 1, 'C', true);

        }
        else 
        {
            $pdf->SetX(15);
            $pdf->SetFillColor(202, 219, 255);
            $pdf->Cell(142, 5, utf8_decode('Avance du jour'), 0, 0, 'R');
            $pdf->Cell(40, 5, utf8_decode(number_format($facture->getAvance(), 0, '', ' ')." FCFA"), 1, 1, 'C', true);
            
            $pdf->SetX(15);
            $pdf->SetFillColor(202, 219, 255);
            $pdf->Cell(142, 5, utf8_decode('Total avance'), 0, 0, 'R');
            $pdf->Cell(40, 5, utf8_decode(number_format($facture->getNetApayer() - $facture->getReste(), 0, '', ' ')." FCFA"), 1, 1, 'C', true);
            
            
            $pdf->SetX(15);
            $pdf->SetFillColor(202, 219, 255);
            $pdf->Cell(142, 5, utf8_decode('Reste à payer'), 0, 0, 'R');
            $pdf->Cell(40, 5, utf8_decode(number_format($facture->getReste(), 0, '', ' ')." FCFA"), 1, 1, 'C', true);

        }
        
        $pdf->Ln(-5);
        $pdf->SetX($pdf->GetX() + 15);
        $pdf->SetY($pdf->GetY() + 15);
        $pdf->SetFont('Arial', 'BU', 12);
        $pdf->Cell(142, 5, utf8_decode('LA CAISSIERE'), 0, 0, 'R');

        $pdf->AliasNbPages();
        return $pdf;
    }
}

