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
class CaissesSepareesDunePeriodeController extends AbstractController
{
    public function __construct(
        protected FactureRepository $factureRepository,
        protected TypeProduitRepository $typeProduitRepository,
        protected ModePaiementRepository $modePaiementRepository,
        protected LigneDeFactureRepository $ligneDeFactureRepository,
    )
    {}

    #[Route('/caisses-separees-d-une-periode', name: 'caisses_separees_d_une_periode')]
    public function caissesSepareesDunePeriode(Request $request): Response
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
        
        $dateDebut = "";
        $dateFin = "";
        $facturesDuJour = "";
        $recettesDuJour = "";
        $recettesKitDuJour ="";

        $afficher = false;

        if ($request->request->has('dateDebut') && $request->request->has('dateFin') && $request->request->has('afficher')) 
        {
            $dateDebut = date_create($request->request->get('dateDebut'));
            $dateFin = date_create($request->request->get('dateFin'));
            
            // $recettesDuJour = $this->ligneDeFactureRepository->recetteDunePeriode($dateDebut, $dateFin);
            $recettesDuJour = $this->factureRepository->recetteDunePeriode($dateDebut, $dateFin);

            $recettesKitDuJour = $this->factureRepository->kitsVenduParCaissiereDunePeriode($dateDebut, $dateFin);

            #je récupère les factures du jour
            $facturesDuJour = $this->factureRepository->facturePeriodeDonnee($dateDebut, $dateFin);

            $afficher = true;
        }

        return $this->render('caisses_separees/caissesSepareesDunePeriode.html.twig', [
            'licence' => 1,
            'afficher' => $afficher,
            'aujourdhui' => "",
            'dateDebut' => $dateDebut,
            'dateFin' => $dateFin,
            'dateDuJour' => "",
            'facturesDuJour' => $facturesDuJour,
            'recettesDuJour' => $recettesDuJour,
            'recettesKitDuJour' => $recettesKitDuJour,
        ]);
    }
}
