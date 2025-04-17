<?php

namespace App\Controller\Impression;

use DateTime;
use App\Entity\ConstantsClass;
use App\Repository\FactureRepository;
use App\Repository\UserRepository;
use App\Service\ImpressionFicheDeVenteService;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;

/**
 * @IsGranted("ROLE_USER", message="Accès refusé. Espace reservé uniquement aux abonnés")
 */
class ImpressionFicheDeVenteController extends AbstractController
{
    public function __construct(
        protected FactureRepository $factureRepository,
        protected UserRepository $userRepository,
        protected ImpressionFicheDeVenteService $impressionFicheDeVenteService
    )
    {}

    #[Route('/impression-fiche-de-vente/{id}/{recette}/{recettePeriode}/{dateDebut}/{dateFin}/{periode}', name: 'impression_fiche_de_vente')]
    public function impressionFicheDeVente(Request $request, int $id = 0, int $recette = 0, int $recettePeriode = 0, $dateDebut = 0, $dateFin = 0, $periode = 0): Response
    {
        # je récupère ma session
        $maSession = $request->getSession();

        if(!$maSession)
        {
            return $this->redirectToRoute("app_logout");
        }
        
        #mes variables témoin pour afficher les sweetAlert
        $maSession->set('ajout', null);
        $maSession->set('suppression', null);

        /**
         *@var User
         */
        $user = $this->getUser();

        if (in_array(ConstantsClass::ROLE_CAISSIERE, $user->getRoles())) 
        {
            $caissiere = $this->userRepository->find($user->getId());
        } 
        else 
        {
            $caissiere = null;
        }
        
        if ($id !=0) 
        {
            $caissiere = $this->userRepository->find($id);

            #date du jour
            $aujourdhui = date_create(date_format(new DateTime('now'), 'Y-m-d'), timezone_open('Pacific/Nauru'));

            $facturesDuJour = $this->factureRepository->findBy([
                'caissiere' => $caissiere,
                'dateFactureAt' => $aujourdhui,
                'annulee' => 0
            ], ['dateFactureAt' => 'DESC']);

            #mes variables
            $nombreCashDuJour = 0;
            $montantCashDuJour = 0;
            $montantAvanceCashDuJour = 0;
            
            $nombrePrisEnChargeDuJour = 0;
            $montantPrisEnChargeDuJour = 0;
            $montantAvancePrisEnChargeDuJour = 0;
            
            $nombreCreditDuJour = 0;
            $montantCreditDuJour = 0;
            $montantAvanceCreditDuJour = 0;
            

            #tableau des factures 
            $cashs = [];
            $prisEnCharges = [];
            $credits = [];

            foreach ($facturesDuJour as $factureDuJour) 
            {
                switch ($factureDuJour->getModePaiement()->getModePaiement()) {
                    case ConstantsClass::CASH:
                        $nombreCashDuJour = $nombreCashDuJour + 1;
                        $montantCashDuJour += $factureDuJour->getNetApayer();
                        $montantAvanceCashDuJour += $factureDuJour->getAvance();

                        $cashs[] = $factureDuJour;
                        break;

                    case ConstantsClass::PRIS_EN_CHARGE:
                        $nombrePrisEnChargeDuJour = $nombrePrisEnChargeDuJour + 1;
                        $montantPrisEnChargeDuJour += $factureDuJour->getNetApayer();
                        $montantAvancePrisEnChargeDuJour += $factureDuJour->getAvance();
                        $prisEnCharges[] = $factureDuJour;
                        break;

                    case ConstantsClass::CREDIT:
                        $nombreCreditDuJour = $nombreCreditDuJour + 1;
                        $montantCreditDuJour += $factureDuJour->getNetApayer();
                        $montantAvanceCreditDuJour += $factureDuJour->getAvance();
                        $credits[] = $factureDuJour;
                        break;
                    
                }
            }
            
            $pdf = $this->impressionFicheDeVenteService->impressionFiheDeVente($nombreCashDuJour, $montantCashDuJour, $nombrePrisEnChargeDuJour, $montantPrisEnChargeDuJour, $nombreCreditDuJour, $montantCreditDuJour, $cashs, $prisEnCharges, $credits, $montantAvanceCashDuJour,
            $montantAvancePrisEnChargeDuJour, $montantAvanceCreditDuJour, $caissiere);
            

        } 
        elseif($recette != 0)
        {
            $caissiere = $this->userRepository->find($recette);

            $facturesDuJour = $this->factureRepository->findBy([
                'caissiere' => $caissiere,
                'annulee' => 0
            ], ['dateFactureAt' => 'DESC']);
            
            #mes variables
            $nombreCashDuJour = 0;
            $montantCashDuJour = 0;
            $montantAvanceCashDuJour = 0;
            

            $nombrePrisEnChargeDuJour = 0;
            $montantPrisEnChargeDuJour = 0;
            $montantAvancePrisEnChargeDuJour = 0;
            

            $nombreCreditDuJour = 0;
            $montantCreditDuJour = 0;
            $montantAvanceCreditDuJour = 0;
            

            #tableau des factures 
            $cashs = [];
            $prisEnCharges = [];
            $credits = [];

            foreach ($facturesDuJour as $factureDuJour) 
            {
                switch ($factureDuJour->getModePaiement()->getModePaiement()) {
                    case ConstantsClass::CASH:
                        $nombreCashDuJour = $nombreCashDuJour + 1;
                        $montantCashDuJour += $factureDuJour->getNetApayer();
                        $montantAvanceCashDuJour += $factureDuJour->getAvance();
                        $cashs[] = $factureDuJour;
                        break;

                    case ConstantsClass::PRIS_EN_CHARGE:
                        $nombrePrisEnChargeDuJour = $nombrePrisEnChargeDuJour + 1;
                        $montantPrisEnChargeDuJour += $factureDuJour->getNetApayer();
                        $montantAvancePrisEnChargeDuJour += $factureDuJour->getAvance();
                        $prisEnCharges[] = $factureDuJour;
                        break;

                    case ConstantsClass::CREDIT:
                        $nombreCreditDuJour = $nombreCreditDuJour + 1;
                        $montantCreditDuJour += $factureDuJour->getNetApayer();
                        $montantAvanceCreditDuJour += $factureDuJour->getAvance();
                        $credits[] = $factureDuJour;
                        break;
                    
                }
            }
           
            $pdf = $this->impressionFicheDeVenteService->impressionFiheDeVente($nombreCashDuJour, $montantCashDuJour, $nombrePrisEnChargeDuJour, $montantPrisEnChargeDuJour, $nombreCreditDuJour, $montantCreditDuJour, $cashs, $prisEnCharges, $credits, $montantAvanceCashDuJour,
            $montantAvancePrisEnChargeDuJour,$montantAvanceCreditDuJour, $caissiere);
            
        }
        elseif ($request->request->has('impressionFicheVente')) 
        {
            // $dateDebut = DateTime::createFromFormat('Y-m-d',$request->request->get('dateDebut'));
            $dateDebut = date_create($request->request->get('dateDebut'));
            $dateFin = date_create($request->request->get('dateFin'));

            $etatFacture = null;
            #ses factures du jours
            if (in_array(ConstantsClass::ROLE_CAISSIERE, $user->getRoles())) 
            {
                $facturesDuJour = $this->factureRepository->facturePeriode($caissiere, $etatFacture, $dateDebut, $dateFin);
            } 
            else 
            {
                $facturesDuJour = $this->factureRepository->facturePeriode($caissiere, $etatFacture, $dateDebut, $dateFin);
            }
            
            
            #mes variables
            $nombreCashDuJour = 0;
            $montantCashDuJour = 0;
            $montantAvanceCashDuJour = 0;
            

            $nombrePrisEnChargeDuJour = 0;
            $montantPrisEnChargeDuJour = 0;
            $montantAvancePrisEnChargeDuJour = 0;
            

            $nombreCreditDuJour = 0;
            $montantCreditDuJour = 0;
            $montantAvanceCreditDuJour = 0;
            

            #tableau des factures 
            $cashs = [];
            $prisEnCharges = [];
            $credits = [];

            foreach ($facturesDuJour as $factureDuJour) 
            {
                switch ($factureDuJour->getModePaiement()->getModePaiement()) {
                    case ConstantsClass::CASH:
                        $nombreCashDuJour = $nombreCashDuJour + 1;
                        $montantCashDuJour += $factureDuJour->getNetApayer();
                        $montantAvanceCashDuJour += $factureDuJour->getAvance();
                        $cashs[] = $factureDuJour;
                        break;

                    case ConstantsClass::PRIS_EN_CHARGE:
                        $nombrePrisEnChargeDuJour = $nombrePrisEnChargeDuJour + 1;
                        $montantPrisEnChargeDuJour += $factureDuJour->getNetApayer();
                        $montantAvancePrisEnChargeDuJour += $factureDuJour->getAvance();
                        $prisEnCharges[] = $factureDuJour;
                        break;

                    case ConstantsClass::CREDIT:
                        $nombreCreditDuJour = $nombreCreditDuJour + 1;
                        $montantCreditDuJour += $factureDuJour->getNetApayer();
                        $montantAvanceCreditDuJour += $factureDuJour->getAvance();
                        $credits[] = $factureDuJour;
                        break;
                    
                }
            }
           
            $caissiere = $this->userRepository->find($user->getId());

            $pdf = $this->impressionFicheDeVenteService->impressionFiheDeVente($nombreCashDuJour, $montantCashDuJour, $nombrePrisEnChargeDuJour, $montantPrisEnChargeDuJour, $nombreCreditDuJour, $montantCreditDuJour, $cashs, $prisEnCharges, $credits, $montantAvanceCashDuJour,
            $montantAvancePrisEnChargeDuJour, $montantAvanceCreditDuJour, $caissiere, $dateDebut, $dateFin, 1);

        }
        elseif ($recettePeriode != 0) 
        {
            $caissiere = $this->userRepository->find($recettePeriode);

            $etatFacture = null;
            // $dateDebut = DateTime::createFromFormat('Y-m-d',$request->request->get('dateDebut'));
            $dateDebut = date_create($dateDebut);
            $dateFin = date_create($dateFin);

            $facturesDuJour = $this->factureRepository->facturePeriode($caissiere, $etatFacture, $dateDebut, $dateFin);
            
            #mes variables
            $nombreCashDuJour = 0;
            $montantCashDuJour = 0;
            $montantAvanceCashDuJour = 0;
            

            $nombrePrisEnChargeDuJour = 0;
            $montantPrisEnChargeDuJour = 0;
            $montantAvancePrisEnChargeDuJour = 0;
            

            $nombreCreditDuJour = 0;
            $montantCreditDuJour = 0;
            $montantAvanceCreditDuJour = 0;
            

            #tableau des factures 
            $cashs = [];
            $prisEnCharges = [];
            $credits = [];

            foreach ($facturesDuJour as $factureDuJour) 
            {
                switch ($factureDuJour->getModePaiement()->getModePaiement()) {
                    case ConstantsClass::CASH:
                        $nombreCashDuJour = $nombreCashDuJour + 1;
                        $montantCashDuJour += $factureDuJour->getNetApayer();
                        $montantAvanceCashDuJour += $factureDuJour->getAvance();
                        $cashs[] = $factureDuJour;
                        break;

                    case ConstantsClass::PRIS_EN_CHARGE:
                        $nombrePrisEnChargeDuJour = $nombrePrisEnChargeDuJour + 1;
                        $montantPrisEnChargeDuJour += $factureDuJour->getNetApayer();
                        $montantAvancePrisEnChargeDuJour += $factureDuJour->getAvance();
                        $prisEnCharges[] = $factureDuJour;
                        break;

                    case ConstantsClass::CREDIT:
                        $nombreCreditDuJour = $nombreCreditDuJour + 1;
                        $montantCreditDuJour += $factureDuJour->getNetApayer();
                        $montantAvanceCreditDuJour += $factureDuJour->getAvance();
                        $credits[] = $factureDuJour;
                        break;
                    
                }
            }
            
            $caissiere = $this->userRepository->find($user->getId());

            $pdf = $this->impressionFicheDeVenteService->impressionFiheDeVente($nombreCashDuJour, $montantCashDuJour, $nombrePrisEnChargeDuJour, $montantPrisEnChargeDuJour, $nombreCreditDuJour, $montantCreditDuJour, $cashs, $prisEnCharges, $credits, $montantAvanceCashDuJour,$montantAvancePrisEnChargeDuJour,$montantAvanceCreditDuJour, $caissiere, $dateDebut, $dateFin, 1);

        }
        else 
        {
            #date du jour
            $aujourdhui = date_create(date_format(new DateTime('now'), 'Y-m-d'), timezone_open('Pacific/Nauru'));

            #ses factures du jours
            if (in_array(ConstantsClass::ROLE_CAISSIERE, $user->getRoles())) 
            {
                $facturesDuJour = $this->factureRepository->findBy([
                    'caissiere' => $user,
                    'dateFactureAt' => $aujourdhui,
                    'annulee' => 0
                ], ['dateFactureAt' => 'DESC']);
            } 
            else 
            {
                $facturesDuJour = $this->factureRepository->findBy([
                    'dateFactureAt' => $aujourdhui,
                    'annulee' => 0
                ], ['dateFactureAt' => 'DESC']);
            }
            
           
            #mes variables
            $nombreCashDuJour = 0;
            $montantCashDuJour = 0;
            $montantAvanceCashDuJour = 0;
            

            $nombrePrisEnChargeDuJour = 0;
            $montantPrisEnChargeDuJour = 0;
            $montantAvancePrisEnChargeDuJour = 0;
            

            $nombreCreditDuJour = 0;
            $montantCreditDuJour = 0;
            $montantAvanceCreditDuJour = 0;
            

            #tableau des factures 
            $cashs = [];
            $prisEnCharges = [];
            $credits = [];

            foreach ($facturesDuJour as $factureDuJour) 
            {
                switch ($factureDuJour->getModePaiement()->getModePaiement()) {
                    case ConstantsClass::CASH:
                        $nombreCashDuJour = $nombreCashDuJour + 1;
                        $montantCashDuJour += $factureDuJour->getNetApayer();
                        $montantAvanceCashDuJour += $factureDuJour->getAvance();
                        $cashs[] = $factureDuJour;
                        break;

                    case ConstantsClass::PRIS_EN_CHARGE:
                        $nombrePrisEnChargeDuJour = $nombrePrisEnChargeDuJour + 1;
                        $montantPrisEnChargeDuJour += $factureDuJour->getNetApayer();
                        $montantAvancePrisEnChargeDuJour += $factureDuJour->getAvance();
                        $prisEnCharges[] = $factureDuJour;
                        break;

                    case ConstantsClass::CREDIT:
                        $nombreCreditDuJour = $nombreCreditDuJour + 1;
                        $montantCreditDuJour += $factureDuJour->getNetApayer();
                        $montantAvanceCreditDuJour += $factureDuJour->getAvance();
                        $credits[] = $factureDuJour;
                        break;
                    
                }
            }

            $pdf = $this->impressionFicheDeVenteService->impressionFiheDeVente($nombreCashDuJour, $montantCashDuJour, $nombrePrisEnChargeDuJour, $montantPrisEnChargeDuJour, $nombreCreditDuJour, $montantCreditDuJour, $cashs, $prisEnCharges, $credits,  $montantAvanceCashDuJour,
            $montantAvancePrisEnChargeDuJour, $montantAvanceCreditDuJour, $caissiere,);

        }
        
        if ($dateDebut && $dateFin) 
        {
            return new Response($pdf->Output(utf8_decode("Fiche de vente période du ".date_format($dateDebut, "d-m-Y")." au ".date_format($dateFin, "d-m-Y")." de ".$caissiere->getNom()) , "I"), 200, ['content-type' => 'application/pdf']);
        } 
        else 
        {
            return new Response($pdf->Output(utf8_decode("Fiche de vente du ".date_format(new DateTime('now'), "d-m-Y")) , "I"), 200, ['content-type' => 'application/pdf']);
        }
        
    }
}
