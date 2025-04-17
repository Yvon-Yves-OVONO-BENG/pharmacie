<?php

namespace App\Controller\Panier;

use App\Service\PanierService;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * @IsGranted("ROLE_USER", message="Accès refusé. Espace reservé uniquement aux abonnés")
 *
 */
class CompterProduitPanierController extends AbstractController
{
    public function __construct(
        protected PanierService $panierService,  
        )
    {}

    #[Route('/compter-produits-panier', name: 'compter_produits_panier', methods:"GET")]
    public function compterProduitPanier(Request $request): JsonResponse
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

        $nombreProduits = count($this->panierService->getDetailsPanierProduits($request));
        $totalApayer = $this->panierService->getTotal($request);

        $maSession->set('nombreProduits', $nombreProduits);
        $maSession->set('totalApayer', $totalApayer);
        
        return new JsonResponse(['success' => true, 'nombreProduit' => $nombreProduits, 'totalApayer' => $totalApayer ]);
       
    }

}