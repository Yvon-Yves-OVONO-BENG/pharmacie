<?php

namespace App\Controller\Facture;

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
class ListeFactureAnnuleeController extends AbstractController
{
    public function __construct(
        protected FactureRepository $factureRepository, 
        protected EtatFactureRepository $etatFactureRepository)
    {}

    #[Route('/liste-facture-annulee', name: 'liste_facture_annulee')]
    public function listeFacture(Request $request): Response
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
        
        // Sinon j'affiche toutes les factures
        $factures = $this->factureRepository->findBy([
                'annulee' => 1
            ], ['dateFactureAt' => 'DESC']);
        
        $etatFactures = $this->etatFactureRepository->findAll();

        return $this->render('facture/listeFacture.html.twig', [
            'licence' => 1,
            'factures' => compact("factures"),
            'etatFactures' => $etatFactures,
            'factureAnnulee' => 1
        ]);
    }
}