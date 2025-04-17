<?php

namespace App\Service;

use Fpdf\Fpdf;
use App\Service\EntetePaysage;
use App\Service\EntetePortrait;
use App\Entity\ElementsPiedDePage\PaginationPortrait;



class ImpressionDesFactureService extends FPDF
{
    public function __construct(
        protected EntetePaysage $entetePaysage, 
        protected EntetePortrait $entetePortrait,
        )
    {
    }

    /**
     * function qui imprime les factures non soldées d'un patient
     *
     * @param array $factures
     * @return PaginationPortrait
     */
    public function impressionDesFactures(array $factures): PaginationPortrait
    {
        $pdf = new PaginationPortrait();

        foreach ($factures as $factur) 
        {
            $pdf->addPage('P');

            $pdf = $this->entetePortrait->entetePortrait($pdf);

            $pdf->SetLeftMargin(10);

            $positionY = 50;
            $pdf->SetXY(15, $positionY);

            // $pdf->Image('../public/images/qrcode/'.$facture->getQrCode(), 10, 40, 500);
            // $pdf->Image('../public/images/qrcode/'.$facture->getQrCode(), 165, 67, 34, 34);
            
            $pdf->Ln(5);
            $pdf->SetFont('Arial', 'B', 12);
            $pdf->Cell(100, 5, 'DETAILS DE LA FACTURE : '.$factur->getReference(), 0, 0, 'L', 0);

            $pdf->SetFont('Arial', 'B', 8);
            $pdf->Cell(80.5, 5, 'Date de la facture : '.date_format($factur->getDateFactureAt(), 'd/m/Y').utf8_decode(' à ').date_format($factur->getHeure(), 'H:i:s'), 0, 1, 'R', 0);
            $pdf->SetFont('Arial', 'I', 10);
            $pdf->SetX(15);
            $pdf->Cell(50, 5, 'DETAILS OF ORDER', 0, 0, 'L', 0);

            $pdf->SetFont('Arial', 'B', 8);
            $pdf->Cell(87, 5, 'PAR : ', 0, 0, 'R', 0);
            $pdf->Cell(50, 5, $factur->getCaissiere() ? utf8_decode($factur->getCaissiere()->getNom()) : "CAISSIERE", 0, 1, 'L', 0);

            $pdf->Ln(5);
            $pdf->SetX(15);
            $pdf->SetFont('Arial', '', 10);

            if ($factur->getPatient()) 
            {
                $pdf->Cell(0, 5, utf8_decode("Salut ".$factur->getPatient()->getNom()), 0, 1, 'L', 0);
                $pdf->Cell(0, 5, utf8_decode("Date de naissance : ".date_format($factur->getPatient()->getDateNaissanceAt(), 'd-m-Y')), 0, 1, 'L', 0);
            } 
            else 
            {
                $pdf->Cell(0, 5, utf8_decode("Salut ".$factur->getNomPatient()), 0, 1, 'L', 0);
            }
            
            
            $pdf->SetX(15);
            $pdf->SetFont('Arial', '', 10);
            $pdf->Cell(0, 5, utf8_decode("Téléphone : ".$factur->getContactPatient() ? $factur->getContactPatient() : "".","), 0, 1, 'L', 0);

            $pdf->SetFont('Arial', '', 10);
            $pdf->SetX(15);
            $pdf->Cell(40, 5, utf8_decode("Etat de la facture : "), 0, 0, 'L', 0);
            $pdf->SetFont('Arial', 'B', 10);
            $pdf->Cell(0, 5, utf8_decode($factur->getEtatFacture() ? $factur->getEtatFacture()->getEtatFacture() : ""), 0, 1, 'L', 0);

            $pdf->SetX(15);
            $pdf->SetFont('Arial', '', 10);
            $pdf->Cell(45, 5, utf8_decode("Mode de paiement choisi : "), 0, 0, 'L', 0);
            $pdf->SetFont('Arial', 'B', 10);
            $pdf->Cell(0, 5, utf8_decode($factur->getModePaiement() ? $factur->getModePaiement()->getModePaiement() : ""), 0, 1, 'L', 0);

            $pdf->SetX(15);
            $pdf->Cell(75, 5, utf8_decode("Cette facture s'élève à un montant de : "), 0, 0, 'L', 0);
            $pdf->SetFont('Arial', 'B', 10);
            $pdf->Cell(0, 5, utf8_decode($factur->getNetApayer()." FCFA"), 0, 1, 'L', 0);

            $positionY = 80;
            $pdf->Ln(5);
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
            foreach ($factur->getLigneDeFactures() as $detailsFactur) 
            {
                if ($i % 2 == 0) 
                {
                    $pdf->SetFillColor(202,219,255);
                }else {
                    $pdf->SetFillColor(255,255,255);
                }
                $pdf->SetX(15);
                $pdf->SetFont('Arial', '', 8);
                $pdf->Cell(7, 5, utf8_decode($i), 1, 0, 'C', true);
                $pdf->Cell(90, 5, utf8_decode($detailsFactur->getProduit()->getLibelle() ? $detailsFactur->getProduit()->getLibelle() : $detailsFactur->getProduit()->getLibelle()), 1, 0, 'L', true);
                $pdf->Cell(25, 5, utf8_decode(number_format($detailsFactur->getPrix(), 0, '', ' ')), 1, 0, 'C', true);
                $pdf->Cell(20, 5, utf8_decode(number_format($detailsFactur->getQuantite(), 0, '', ' ')), 1, 0, 'C', true);
                $pdf->Cell(40, 5, utf8_decode(number_format($detailsFactur->getPrixQuantite(), 0, '', ' ')), 1, 1, 'C', true);

                $i++;
                
            }

            $pdf->SetX(15);
            $pdf->SetFont('Arial', 'B', 10);
            $pdf->Cell(142, 5, utf8_decode('Montant HT'), 0, 0, 'R');
            $pdf->Cell(40, 5, utf8_decode(number_format($factur->getNetApayer(), 0, '', ' ')), 1, 1, 'C');

            $pdf->SetX(15);
            $pdf->Cell(142, 5, utf8_decode('TVA'), 0, 0, 'R');
            $pdf->Cell(40, 5, utf8_decode("0%"), 1, 1, 'C');

            $pdf->SetX(15);
            $pdf->Cell(142, 5, utf8_decode('Montant TTC'), 0, 0, 'R');
            $pdf->Cell(40, 5, utf8_decode(number_format($factur->getNetApayer(), 0, '', ' ')), 1, 1, 'C');

            $pdf->SetFont('Arial', 'B', 12);
            $pdf->SetX(15);
            $pdf->SetFillColor(202, 219, 255);
            $pdf->Cell(142, 5, utf8_decode('NET A PAYER'), 0, 0, 'R');
            $pdf->Cell(40, 5, utf8_decode(number_format($factur->getNetApayer(), 0, '', ' ')." FCFA"), 1, 1, 'C', true);
            
            $pdf->SetX(15);
            $pdf->SetFillColor(202, 219, 255);
            $pdf->Cell(142, 5, utf8_decode('AVANCE'), 0, 0, 'R');
            $pdf->Cell(40, 5, utf8_decode(number_format($factur->getAvance(), 0, '', ' ')." FCFA"), 1, 1, 'C', true);

            $pdf->SetX(15);
            $pdf->SetFillColor(202, 219, 255);
            $pdf->Cell(142, 5, utf8_decode('RESTE'), 0, 0, 'R');
            $pdf->Cell(40, 5, utf8_decode(number_format(($factur->getNetApayer() - $factur->getAvance()), 0, '', ' ')." FCFA"), 1, 1, 'C', true);

            $pdf->SetX($pdf->GetX() + 15);
            $pdf->SetY($pdf->GetY() + 15);
            $pdf->SetFont('Arial', 'BU', 12);
            $pdf->Cell(142, 5, utf8_decode('LA CAISSIERE'), 0, 0, 'R');

        }

        
        return $pdf;
    }
}

