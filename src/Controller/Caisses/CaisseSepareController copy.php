<?php

namespace App\Controller\Caisse;

use App\Entity\ConstantsClass;
use App\Repository\FactureRepository;
use App\Repository\LigneDeFactureRepository;
use App\Repository\ModePaiementRepository;
use App\Repository\TypeProduitRepository;
use DateTime;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;

/**
 * @IsGranted("ROLE_USER", message="Accès refusé. Espace reservé uniquement aux abonnés")
 *
 */
#[Route('/caisse-separes', name: 'caisse_separes')]
class CaisseSepareController extends AbstractController
{
    public function __construct(
        protected FactureRepository $factureRepository,
        protected TypeProduitRepository $typeProduitRepository,
        protected ModePaiementRepository $modePaiementRepository,
        protected LigneDeFactureRepository $ligneDeFactureRepository,
    )
    {}

    #[Route('/caisse-separes', name: 'caisse_separes')]
    public function CaisseSepare(Request $request): Response
    {
        # je récupère ma session
        $maSession = $request->getSession();

        if(!$maSession)
        {
            return $this->redirectToRoute("app_logout");
        }
        
        #mes variables témoin pour afficher les sweetAlert
        $maSession->set('ajout', null);
        $maSession->set('misAjour', null);
        $maSession->set('suppression', null);
        
        #la date du jour
        $aujourdhui = new DateTime('now');

        #je récupère les factures du jour
        $facturesDuJour = $this->factureRepository->findBy([
            'dateFactureAt' => $aujourdhui,
            'annulee' => 0
        ], ['dateFactureAt' => 'DESC']);
        
        ################################################
        #je récupère les produits classiques vendus du jour
        $produits = $this->ligneDeFactureRepository->chercheLesMedicamentsVendusDujour($aujourdhui);
        
        #je récupère les kits vendus du jour
        $kits = $this->ligneDeFactureRepository->chercheLesKitsVendusDujour($aujourdhui);
       
        #je récupère ces kits pour récupérer les produits de cds kits
        $kitVendus = $this->ligneDeFactureRepository->kitsVendusDuJour($aujourdhui);

        #mes variables des produit kit
        $nbreMedicamentsCash = 0;
        $nbreActeCash = 0;
        $nbreExamenCash = 0;

        $nbreMedicamentsCredit = 0;
        $nbreActeCredit = 0;
        $nbreExamenCredit = 0;

        $nbreMedicamentsPrisEnCharge = 0;
        $nbreActePrisEnCharge = 0;
        $nbreExamenPrisEnCharge = 0;

        foreach ($kitVendus as $kit) 
        {
            switch ($kit->getFacture()->getModePaiement()->getModePaiement())
            {
                case ConstantsClass::CASH:
                   
                    foreach ($kit->getProduit()->getProduitLigneDeKits() as $ligneDeKit) 
                    {
                        switch ($ligneDeKit->getProduit()->getLot()->getTypeProduit()->getTypeProduit()) 
                        {
                            case ConstantsClass::MEDICAMENT:
                                $nbreMedicamentsCash += $ligneDeKit->getQuantite() * $kit->getQuantite() ;
                                break;
                            
                            case ConstantsClass::ACTE:
                                $nbreActeCash += $ligneDeKit->getQuantite() * $kit->getQuantite() ;
                                break;

                            case ConstantsClass::EXAMEN:
                                $nbreExamenCash += $ligneDeKit->getQuantite() * $kit->getQuantite() ;
                        }
                    }
                    break;
                
                case ConstantsClass::PRIS_EN_CHARGE:
                    foreach ($kit->getProduit()->getProduitLigneDeKits() as $ligneDeKit) 
                    {
                        switch ($ligneDeKit->getProduit()->getLot()->getTypeProduit()->getTypeProduit()) 
                        {
                            case ConstantsClass::MEDICAMENT:
                                $nbreMedicamentsPrisEnCharge += $ligneDeKit->getQuantite() * $kit->getQuantite() ;
                                break;
                            
                            case ConstantsClass::ACTE:
                                $nbreActePrisEnCharge += $ligneDeKit->getQuantite() * $kit->getQuantite() ;
                                break;

                            case ConstantsClass::EXAMEN:
                                $nbreExamenPrisEnCharge += $ligneDeKit->getQuantite() * $kit->getQuantite() ;
                        }
                    }
                    break;

                case ConstantsClass::CREDIT:
                    foreach ($kit->getProduit()->getProduitLigneDeKits() as $ligneDeKit) 
                    {
                        switch ($ligneDeKit->getProduit()->getLot()->getTypeProduit()->getTypeProduit()) 
                        {
                            case ConstantsClass::MEDICAMENT:
                                $nbreMedicamentsCredit += $ligneDeKit->getQuantite() * $kit->getQuantite() ;
                                break;
                            
                            case ConstantsClass::ACTE:
                                $nbreActeCredit += $ligneDeKit->getQuantite() * $kit->getQuantite() ;
                                break;

                            case ConstantsClass::EXAMEN:
                                $nbreExamenCredit += $ligneDeKit->getQuantite() * $kit->getQuantite() ;
                        }
                    }
                    break;
            }
        }


        #mes variables des kits
        $nombreKitCash = 0;
        $montantKitCash = 0;

        $nombreKitCredit = 0;
        $montantKitCredit = 0;

        $nombreKitPrisEnCharge = 0;
        $montantKitPrisEnCharge = 0;

        foreach ($kits as $kit) 
        {
            switch ($kit["modePaiement"]) 
            {
                case ConstantsClass::CASH:
                    $nombreKitCash += $nombreKitCash + (int)$kit["quantiteFacture"];
                    $montantKitCash += $kit['montant'];
                    break;

                case ConstantsClass::CREDIT:
                    $nombreKitCredit = $nombreKitCredit + (int)$kit["quantiteFacture"];
                    $montantKitCredit += $kit['montant'];
                    break;

                case ConstantsClass::PRIS_EN_CHARGE:
                    $nombreKitPrisEnCharge = $nombreKitPrisEnCharge + (int)$kit["quantiteFacture"];
                    $montantKitPrisEnCharge += $kit['montant'];
                    break;
                
                
            }
        }

        #mes variables des produits classiques
        $nombreMedicamentsCash = 0;
        $montantMedicamentsCash = 0;

        $nombreMedicamentsCredit = 0;
        $montantMedicamentsCredit = 0;

        $nombreMedicamentsPrisEnCharge = 0;
        $montantMedicamentsPrisEnCharge = 0;

        #############################
        $nombreActeCash = 0;
        $montantActeCash = 0;

        $nombreActeCredit = 0;
        $montantActeCredit = 0;

        $nombreActePrisEnCharge = 0;
        $montantActePrisEnCharge = 0;

        #############################
        $nombreExamenCash = 0;
        $montantExamenCash = 0;

        $nombreExamenCredit = 0;
        $montantExamenCredit = 0;

        $nombreExamenPrisEnCharge = 0;
        $montantExamenPrisEnCharge = 0;

        foreach ($produits as $produit) 
        {
            switch ($produit['typeProduit']) 
            {
                case ConstantsClass::MEDICAMENT:
                    switch ($produit['modePaiement']) 
                    {
                        case ConstantsClass::CASH:
                            
                            $nombreMedicamentsCash = $nombreMedicamentsCash + (int)$produit['quantiteFacture'];
                            $montantMedicamentsCash += $produit['montant'];
                            
                            break;

                        case ConstantsClass::CREDIT:
                            $nombreMedicamentsCredit = $nombreMedicamentsCredit + (int)$produit['quantiteFacture'];
                            $montantMedicamentsCredit += $produit['montant'];
                            break;

                        case ConstantsClass::PRIS_EN_CHARGE:
                            $nombreMedicamentsPrisEnCharge = $nombreMedicamentsPrisEnCharge + (int)$produit['quantiteFacture'];
                            $montantMedicamentsPrisEnCharge += $produit['montant'];
                            break;
                        
                        
                    }
                    break;

                case ConstantsClass::ACTE:
                    switch ($produit['modePaiement']) 
                    {
                        case ConstantsClass::CASH:
                            $nombreActeCash = $nombreActeCash + (int)$produit['quantiteFacture'];
                            $montantActeCash += $produit['montant'];
                            break;

                        case ConstantsClass::CREDIT:
                            $nombreActeCredit = $nombreActeCredit + (int)$produit['quantiteFacture'];
                            $montantActeCredit += $produit['montant'];
                            break;

                        case ConstantsClass::PRIS_EN_CHARGE:
                            $nombreActePrisEnCharge = $nombreActePrisEnCharge + (int)$produit['quantiteFacture'];
                            $montantActePrisEnCharge += $produit['montant'];
                            break;
                        
                        
                    }
                    
                    break;

                
                case ConstantsClass::EXAMEN:
                    switch ($produit['modePaiement']) 
                    {
                        case ConstantsClass::CASH:
                            $nombreExamenCash = $nombreExamenCash + (int)$produit['quantiteFacture'];
                            $montantExamenCash += $produit['montant'];
                            break;

                        case ConstantsClass::CREDIT:
                            $nombreExamenCredit = $nombreExamenCredit + (int)$produit['quantiteFacture'];
                            $montantExamenCredit += $produit['montant'];
                            break;

                        case ConstantsClass::PRIS_EN_CHARGE:
                            $nombreExamenPrisEnCharge = $nombreExamenPrisEnCharge + (int)$produit['quantiteFacture'];
                            $montantExamenPrisEnCharge += $produit['montant'];
                            break;
                        
                        
                    }
                    
                    break;
                
                
            }  
        }

        return $this->render('caisse_separes/caisseSepare.html.twig', [
            'licence' => 1,
            'aujourdhui' => $aujourdhui,
            'factures' => $facturesDuJour,
            'produits' => $produits,

            #################
            'nombreMedicamentsCash' => $nombreMedicamentsCash + $nbreMedicamentsCash,
            'montantMedicamentsCash' => $montantMedicamentsCash,

            'nombreMedicamentsPrisEnCharge' => $nombreMedicamentsPrisEnCharge + $nbreMedicamentsPrisEnCharge,
            'montantMedicamentsPrisEnCharge' => $montantMedicamentsPrisEnCharge,

            'nombreMedicamentsCredit' => $nombreMedicamentsCredit + $nbreMedicamentsCredit,
            'montantMedicamentsCredit' => $montantMedicamentsCredit,
            ###################
            'nombreActeCash' => $nombreActeCash + $nbreActeCash,
            'montantActeCash' => $montantActeCash,

            'nombreActePrisEnCharge' => $nombreActePrisEnCharge + $nbreActePrisEnCharge,
            'montantActePrisEnCharge' => $montantActePrisEnCharge,

            'nombreActeCredit' => $nombreActeCredit + $nbreActeCredit,
            'montantActeCredit' => $montantActeCredit,
            #####################
            'nombreExamenCash' => $nombreExamenCash + $nbreExamenCash,
            'montantExamenCash' => $montantExamenCash,

            'nombreExamenPrisEnCharge' => $nombreExamenPrisEnCharge + $nbreExamenPrisEnCharge,
            'montantExamenPrisEnCharge' => $montantExamenPrisEnCharge,

            'nombreExamenCredit' => $nombreExamenCredit + $nbreExamenCredit,
            'montantExamenCredit' => $montantExamenCredit,

            #####################
            'nombreKitCash' => $nombreKitCash,
            'montantKitCash' => $montantKitCash,

            'nombreKitPrisEnCharge' => $nombreKitPrisEnCharge,
            'montantKitPrisEnCharge' => $montantKitPrisEnCharge,

            'nombreKitCredit' => $nombreKitCredit,
            'montantKitCredit' => $montantKitCredit,
        ]);
    }
}
