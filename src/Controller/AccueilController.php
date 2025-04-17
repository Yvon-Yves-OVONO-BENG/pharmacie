<?php

namespace App\Controller;

use DateTime;
use App\Entity\ConstantsClass;
use App\Repository\CommandeRepository;
use App\Repository\FactureRepository;
use App\Repository\LicenceRepository;
use App\Repository\LotRepository;
use App\Repository\ProduitRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\RouterInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @IsGranted("ROLE_USER and !user.etat", message="Accès refusé. Espace reservé uniquement aux abonnés")
 *
 */
class AccueilController extends AbstractController
{
    public function __construct(
        protected RouterInterface $router,
        protected EntityManagerInterface $em,
        protected LotRepository $lotRepository,
        protected TranslatorInterface $translator,
        protected UserRepository $userRepository,
        protected LicenceRepository $licenceRepository,
        protected ProduitRepository $produitRepository,
        protected FactureRepository $factureRepository,
        protected CommandeRepository $commandeRepository,
    )
    {}

    #[Route('/accueil', name: 'accueil')]
    public function accueil(Request $request): Response
    {
        $collection = $this->router->getRouteCollection();
        $allRoutes = $collection->all();

        # je récupère ma session
        $maSession = $request->getSession();

        if(!$maSession)
        {
            return $this->redirectToRoute("app_logout");
        }

        #mes variables témoin pour afficher les sweetAlert
        $maSession->set('ajout', null);
        $maSession->set('miseAjour', null);
        $maSession->set('suppression', null);
        
        /**
         *@var User
         */
        $user = $this->getUser();
        
        #si l'utilisateur n'est pas connecté déconnecte le
        if (!$user) 
        {   
            return $this->redirectToRoute('app_logout');
        }

        // $factures = $this->factureRepository->findAll();

        // foreach ($factures as $facture) 
        // {
        //     $facture->setReste($facture->getNetAPayer() - $facture->getAvance());

        //     $this->em->persist($facture);
        // }

        // $this->em->flush();

        $factures = [];
        $facturesDuJour = [];
        $facturesDuJourCaissiere = [];
        #date du jour
        $aujourdhui = new DateTime('now');

        #je formate 'maintenant' pour calculer le nombre de jours restants de la licence
        $maintenant1 = date_format($aujourdhui, 'Y-m-d');
        $maintenant = new DateTime($maintenant1);

        #je récupère la date d'expiration et je la formate
        $dateExpiration1 = date_format($this->licenceRepository->findAll()[0]->getDateExpirationAt(), 'Y-m-d');
        $dateExpiration = new DateTime($dateExpiration1);

        #je calcul la différence
        $dateDiffExpiration = $maintenant->diff($dateExpiration);
        
        #je récupère tous les utilisateurs
        $utilisateurs = $this->userRepository->findAll();
        $nombreJoursRestant = (int)$dateDiffExpiration->format('%R%a');

        #si le nombre de jour restant est supérieur à zéro(0)
        if ((int)$dateDiffExpiration->format('%R%a') >= 0 ) 
        {
            
            $licence = $this->licenceRepository->findAll()[0];

            $licence->setNombreJours($nombreJoursRestant);
            $this->em->persist($licence);
            $this->em->flush();

            //2. Toutes Ses factures
           

            if (in_array(ConstantsClass::ROLE_REGIES_DES_RECETTES, $user->getRoles()) || 
            in_array(ConstantsClass::ROLE_ADMINISTRATEUR, $user->getRoles()) || 
            in_array(ConstantsClass::ROLE_SECRETAIRE, $user->getRoles())) 
            {
                #ses factures du jours
                $facturesDuJour = $this->factureRepository->findBy([
                    'dateFactureAt' => $aujourdhui,
                    'annulee' => 0,
                ], ['dateFactureAt' => 'DESC']);
                
                $factures = $this->factureRepository->findBy([
                    'annulee' => 0,
                ], ['id' => 'DESC']);
            } 
            elseif(in_array(ConstantsClass::ROLE_CAISSIERE, $user->getRoles()))
            {
                #ses factures du jours
                $facturesDuJour = $this->factureRepository->findBy([
                    'dateFactureAt' => $aujourdhui,
                    'annulee' => 0,
                    'caissiere' => $this->userRepository->find($user->getId())
                ], ['dateFactureAt' => 'DESC']);

                $factures = $this->factureRepository->findBy([
                    'annulee' => 0,
                    'caissiere' => $user
                ], ['id' => 'DESC']);
            }
            
            
            #je recupère tous les produits
            $tousLesProduits = $this->produitRepository->findBy([
                'kit' => 0,
                'supprime' => 0,
            ]);

            #je récupère les produits dont la date de premption est non nulle
            $produits = $this->produitRepository->produits();

            #je calcul les produits périmés dans moins de 90 jours
            $produitsBientotPerimes = [];

            foreach ($produits as $produit) 
            {
                $aujourdhui = date_format(new DateTime('now'), 'Y-m-d');
                $aujourdhui = new DateTime($aujourdhui);

                $datePeremption = date_format($produit->getLot()->getDatePeremptionAt(), ('Y-m-d'));
                $datePeremption = new DateTime($datePeremption);

                $dateDiff = $aujourdhui->diff($datePeremption);
                
                if ((int)$dateDiff->format('%R%a') <= 90 && ((int)$dateDiff->format('%R%a') > 0) && ($produit->isSupprime() == 0)) 
                {
                    $produitsBientotPerimes[] = $produit;
                }
            
            }

            #je calcul les produits périmés
            $produitsPerimes = [];

            foreach ($produits as $produit) 
            {
                #je récupère le nombre de jour entre la date du jour et la date de peremption du produit
                $aujourdhui = date_format(new DateTime('now'), 'Y-m-d');
                $aujourdhui = new DateTime($aujourdhui);

                $datePeremption = date_format($produit->getLot()->getDatePeremptionAt(), ('Y-m-d'));
                $datePeremption = new DateTime($datePeremption);

                $dateDiff = $aujourdhui->diff($datePeremption);
                
                if ((int)$dateDiff->format('%R%a') <= 0 && $produit->isSupprime() == 0) 
                {
                    $produitsPerimes[] = $produit;
                }
            
            }

            #les commandes
            #je recupère toutes les commandes pour compter
            // $commandes = $this->commandeRepository->findBy([], ['id' => 'DESC' ]);
            $commandes = $this->commandeRepository->findAll();

            ###############################
            #mes variables
            $nombreCash = 0;
            $montantCash = 0;

            $nombrePrisEnCharge = 0;
            $montantPrisEnCharge = 0;

            $nombreCredit = 0;
            $montantCredit = 0;
            foreach ($factures as $facture) 
            {
                switch ($facture->getModePaiement()->getModePaiement()) {
                    case 'CASH':
                        $nombreCash = $nombreCash + 1;
                        $montantCash += $facture->getAvance();
                        break;

                    case 'PRIS EN CHARGE':
                        $nombrePrisEnCharge = $nombrePrisEnCharge + 1;
                        $montantPrisEnCharge += $facture->getAvance();
                        break;

                    case 'CRÉDIT':
                        $nombreCredit = $nombreCredit + 1;
                        $montantCredit += $facture->getAvance();
                        break;
                    
                }
            }

            ###################
            #mes variables
            $nombreCashDuJour = 0;
            $montantCashDuJour = 0;

            $nombrePrisEnChargeDuJour = 0;
            $montantPrisEnChargeDuJour = 0;

            $nombreCreditDuJour = 0;
            $montantCreditDuJour = 0;

            foreach ($facturesDuJour as $factureDuJour) 
            {
                switch ($factureDuJour->getModePaiement()->getModePaiement()) {
                    case ConstantsClass::CASH:
                        $nombreCashDuJour = $nombreCashDuJour + 1;
                        $montantCashDuJour += $factureDuJour->getAvance();
                        break;

                    case ConstantsClass::PRIS_EN_CHARGE:
                        $nombrePrisEnChargeDuJour = $nombrePrisEnChargeDuJour + 1;
                        $montantPrisEnChargeDuJour += $factureDuJour->getAvance();
                        break;

                    case ConstantsClass::CREDIT:
                        $nombreCreditDuJour = $nombreCreditDuJour + 1;
                        $montantCreditDuJour += $factureDuJour->getAvance();
                        break;
                    
                }
            }

            #je recupère les lots
            $lots = $this->lotRepository->findAll();

            if (in_array(ConstantsClass::ROLE_REGIES_DES_RECETTES, $user->getRoles()) || 
            in_array(ConstantsClass::ROLE_ADMINISTRATEUR, $user->getRoles()) || 
            in_array(ConstantsClass::ROLE_SECRETAIRE, $user->getRoles())) 
            {
                #ses factures du jours de la caisiere
                $facturesDuJourCaissiere = $this->factureRepository->findBy([
                    'dateFactureAt' => $aujourdhui,
                    'annulee' => 0
                ], ['dateFactureAt' => 'DESC']);
            }
            elseif(in_array(ConstantsClass::ROLE_CAISSIERE, $user->getRoles()))
            {
                #ses factures du jours de la caisiere
                $facturesDuJourCaissiere = $this->factureRepository->findBy([
                    'caissiere' => $this->userRepository->find($user->getId()),
                    'dateFactureAt' => $aujourdhui,
                    'annulee' => 0
                ], ['dateFactureAt' => 'DESC']);
            }

            return $this->render('accueil/index.html.twig', [
                'licence' => 1,
                'nombreDeJoursRestants' => $nombreJoursRestant, 
                'tousLesProduits' => $tousLesProduits,
                'commandes' => $commandes,
                'facturesDuJourCaissiere' => $facturesDuJourCaissiere,

                'nombreCash' => $nombreCash,
                'montantCash' => $montantCash,
                'nombrePrisEnCharge' => $nombrePrisEnCharge,
                'montantPrisEnCharge' => $montantPrisEnCharge,
                'nombreCredit' => $nombreCredit,
                'montantCredit' => $montantCredit,

                'nombreCashDuJour' => $nombreCashDuJour,
                'montantCashDuJour' => $montantCashDuJour,
                'nombrePrisEnChargeDuJour' => $nombrePrisEnChargeDuJour,
                'montantPrisEnChargeDuJour' => $montantPrisEnChargeDuJour,
                'nombreCreditDuJour' => $nombreCreditDuJour,
                'montantCreditDuJour' => $montantCreditDuJour,

                'aujourdhui' => $aujourdhui,
                'factures' => compact("factures"),
                
                'produitsPerimes' => $produitsPerimes,
                'produitsBientotPerimes' => $produitsBientotPerimes,

                ##########################
                'lots' => $lots,
                'produits' => $produits,
                'factureAnnulee' => 0,

            ]);
        }
        else
        {
            return $this->render('accueil/index.html.twig', [
                'licence' => 0,
            ]);
        }

        

    }
}
