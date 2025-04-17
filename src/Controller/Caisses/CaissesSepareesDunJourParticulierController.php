<?php

namespace App\Controller\Caisses;

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
#[Route('/caisses')]
class CaissesSepareesDunJourParticulierController extends AbstractController
{
    public function __construct(
        protected FactureRepository $factureRepository,
        protected TypeProduitRepository $typeProduitRepository,
        protected ModePaiementRepository $modePaiementRepository,
        protected LigneDeFactureRepository $ligneDeFactureRepository,
    )
    {}

    #[Route('/caisses-separees-d-un-jour-particulier', name: 'caisses_separees_d_un_jour_particulier')]
    public function caissesSepareesDunJourParticulier(Request $request): Response
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
        
        $dateDuJour = "";
        $facturesDuJour = "";
        $recettesDuJour = "";
        $recettesKitDuJour ="";

        $afficher = false;

        if ($request->request->has('dateDuJour') && $request->request->has('afficher')) 
        {
            $dateDuJour = date_create($request->request->get('dateDuJour'));
            
            $recettesDuJour = $this->ligneDeFactureRepository->recetteDunJourParticulier($dateDuJour);

            $recettesKitDuJour = $this->factureRepository->kitsVenduParCaissiereDunJourParticulier($dateDuJour);

            #je récupère les factures du jour
            $facturesDuJour = $this->factureRepository->findBy([
                'dateFactureAt' => $dateDuJour,
                'annulee' => 0
            ], ['dateFactureAt' => 'DESC']);

            $afficher = true;
        }

        return $this->render('caisses_separees/caissesSepareesDunJourParticulier.html.twig', [
            'licence' => 1,
            'dateDuJour' => $dateDuJour,
            'afficher' => $afficher,
            'facturesDuJour' => $facturesDuJour,
            'recettesDuJour' => $recettesDuJour,
            'recettesKitDuJour' => $recettesKitDuJour,
        ]);
    }
}
