<?php

namespace App\Controller\Recette;

use App\Repository\FactureRepository;
use App\Repository\HistoriquePaiementRepository;
use DateTime;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\Constraints\Date;

/**
 * @IsGranted("ROLE_USER", message="Accès refusé. Espace reservé uniquement aux abonnés")
 *
 */
#[Route('/recette')]
class RecetteCaissiereDuJourController extends AbstractController
{
    public function __construct(
        protected FactureRepository $factureRepository,
        protected HistoriquePaiementRepository $historiquePaiementRepository,
    )
    {}

    #[Route('/recette-caissiere-du-jour', name: 'recette_caissiere_du_jour')]
    public function recetteCaissiereDuJour(Request $request): Response
    {
        # je récupère ma session
        $maSession = $request->getSession();

        if(!$maSession)
        {
            return $this->redirectToRoute("app_logout");
        }

        #date du jour
        $aujourdhui = date_create(date_format(new DateTime('now'), 'Y-m-d'), timezone_open('Pacific/Nauru'));
        // $aujourdhui = new DateTime("now");
        // $date = date_create_from_format('Y-m-d', $aujourdhui);
        // dd($aujourdhui);
        #je récupère les recettes des caissières du jour

        // $avancesDuJour = $this->historiquePaiementRepository->avancesDuJour($aujourdhui);
        $avancesDuJour = $this->factureRepository->recetteAvanceDuJourCaissiere($aujourdhui);
        
        $recettesDuJourSolde = $this->factureRepository->recetteCaissiereSolde($aujourdhui);
        
        $recettesDuJourNonSolde = $this->factureRepository->recetteCaissiereNonSolde($aujourdhui);
        
        $netApayer = 0;
        $recetteAvanceDuJour = 0;
        $recetteDuJourSolde = 0;
        $recetteDuJourNonSolde = 0;
        // dd($avancesDuJour);
        foreach ($avancesDuJour as $avanceDuJour) 
        {
            // $recetteAvanceDuJour += $avanceDuJour->getMontantAvance();
            $recetteAvanceDuJour += $avanceDuJour['avance'];
            $netApayer += $avanceDuJour['netAPayer'];
        }

        foreach ($recettesDuJourSolde as $recette) 
        {
            $recetteDuJourSolde += $recette['SOMME'];
        }

        foreach ($recettesDuJourNonSolde as $recette) 
        {
            $recetteDuJourNonSolde += $recette['SOMME'];
        }
        
        $nombreRecetteDuJour = $this->factureRepository->findBy([
            'dateFactureAt' => $aujourdhui
        ]);
        
        return $this->render('recette_caissiere/recetteDuJour.html.twig', [
            'licence' => 1,
            'recetteAvanceDuJour' => $recetteAvanceDuJour,
            'avancesDuJour' => $avancesDuJour,
            'recetteDuJourSolde' => $recetteDuJourSolde,
            'recetteDuJourNonSolde' => $recetteDuJourNonSolde,
            'recettesDuJourSolde' => $recettesDuJourSolde,
            'recettesDuJourNonSolde' => $recettesDuJourNonSolde,
            'nombreRecetteDuJour' => $nombreRecetteDuJour,
        ]);
    }
}
