<?php

namespace App\Controller\Facture;

use App\Entity\ConstantsClass;
use App\Repository\FactureRepository;
use App\Repository\EtatFactureRepository;
use App\Repository\UserRepository;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;

/**
 * @IsGranted("ROLE_USER", message="Accès refusé. Espace reservé uniquement aux abonnés")
 *
 */
#[Route('/facture')]
class ListeFactureController extends AbstractController
{
    public function __construct(
        protected UserRepository $userRepository, 
        protected FactureRepository $factureRepository, 
        protected EtatFactureRepository $etatFactureRepository,
        )
    {}

    #[Route('/liste-facture/{a<[0-1]{1}>}/{m<[0-1]{1}>}/{solde<[0-1]{1}>}', name: 'liste_facture')]
    public function listeFacture(Request $request, int $a = 0, int $m = 0, int $solde = 0): Response
    {
        # je récupère ma session
        $maSession = $request->getSession();

        if(!$maSession)
        {
            return $this->redirectToRoute("app_logout");
        }
        
        #je teste si le témoin n'est pas vide pour savoir s'il vient de la mise à jour
        if ($a == 1) 
        {
            #mes variables témoin pour afficher les sweetAlert
            $maSession->set('ajout', null);
            $maSession->set('suppression', null);
        }

        #je teste si le témoin n'est pas vide pour savoir s'il vient de la mise à jour
        if ($m == 1) 
        {
            #mes variables témoin pour afficher les sweetAlert
            $maSession->set('ajout', 1);
            $maSession->set('suppression', null);
            
        }

        if ($solde == 1) 
        {
            #mes variables témoin pour afficher les sweetAlert
            $maSession->set('ajout', 1);
            $maSession->set('suppression', null);
            
        }

        //1. Nous devons nous assurer que la caisière est connecté pour accéder à ses factures
        /**
         *@var User
         */
        $user = $this->getUser();
        
        $roles = $user->getRoles();

        if ($user && in_array(ConstantsClass::ROLE_CAISSIERE, $roles) ) 
        {
            $caissiere = $this->userRepository->find($user->getId());
            
            if ($solde) 
            {
                //2. Ses factures
                $factures = $this->factureRepository->getFacturesARegler($caissiere);
                // dd($factures);
            } 
            else 
            {
                //2. Ses factures
                $factures = $this->factureRepository->findBy([
                    'caissiere' => $user,
                    'annulee' => 0
                ], ['id' => 'DESC']);
                // dd($factures);
            }

        } 
        else 
        {
            // Sinon j'affiche toutes les factures
            $factures = $this->factureRepository->findBy([
                'annulee' => 0
            ], ['dateFactureAt' => 'DESC']);
        }

        // Sinon j'affiche toutes les factures
        // $factures = $this->factureRepository->findBy([
        //     'annulee' => 0
        // ], ['dateFactureAt' => 'DESC']);

        $etatFactures = $this->etatFactureRepository->findAll();

        return $this->render('facture/listeFacture.html.twig', [
            'licence' => 1,
            'factures' => compact("factures"),
            'etatFactures' => $etatFactures,
            'factureAnnulee' => 0,
        ]);
    }
}