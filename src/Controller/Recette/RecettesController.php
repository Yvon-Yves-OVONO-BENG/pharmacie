<?php

namespace App\Controller\Recette;

use App\Repository\FactureRepository;
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
#[Route('/recette')]
class RecettesController extends AbstractController
{
    public function __construct(
        protected FactureRepository $factureRepository
    )
    {}

    #[Route('/recettes', name: 'recettes')]
    public function recettes(Request $request): Response
    {
        # je récupère ma session
        $maSession = $request->getSession();

        if(!$maSession)
        {
            return $this->redirectToRoute("app_logout");
        }

        #je récupère les recettes des caissières du jour
        $recettes= $this->factureRepository->recettes();
        
        $nombreRecettes= $this->factureRepository->findAll();

        #je récupère toutes les recettes des caisières 
        return $this->render('recette_caissiere/recettes.html.twig', [
            'licence' => 1,
            'recettes' => $recettes,
            'nombreRecettes' => $nombreRecettes,
        ]);
    }
}
