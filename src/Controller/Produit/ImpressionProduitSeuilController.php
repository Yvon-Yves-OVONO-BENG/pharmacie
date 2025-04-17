<?php

namespace App\Controller\Produit;

use DateTime;
use App\Repository\FactureRepository;
use App\Repository\LigneDeFactureRepository;
use App\Repository\ProduitRepository;
use App\Service\ImpressionProduitSeuilService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

/**
 * @IsGranted("ROLE_USER", message="Accès refusé. Espace reservé uniquement aux abonnés")
 *
 */
class ImpressionProduitSeuilController extends AbstractController
{
    public function __construct(
        protected FactureRepository $factureRepository,
        protected ProduitRepository $produitRepository,
        protected LigneDeFactureRepository $ligneDeFactureRepository,
        protected ImpressionProduitSeuilService $impressionProduitSeuilService
    )
    {}

    #[Route('/impression-produit-seuil', name: 'impression_produit_seuil')]
    public function impressionProduit(Request $request): Response
    {
        # je récupère ma session
        $maSession = $request->getSession();

        if(!$maSession)
        {
            return $this->redirectToRoute("app_logout");
        }

        #je récupères les produits seuils de la base de données
        $produits = [];

        $produis = $this->produitRepository->findBy([
            'kit' => 0,
            'supprime' => 0
        ], ['libelle' => 'ASC' ]);

        foreach ($produis as $produit) 
        {
            if (($produit->getLot()->getQuantite() - $produit->getLot()->getVendu()) <= $produit->getQuantiteSeuil()) 
            {
                $produits[] = $produit;
            }
        }
        
        $pdf = $this->impressionProduitSeuilService->impressionProduitSeuil($produits);

        return new Response($pdf->Output(utf8_decode("Produits seuils"), "I"), 200, ['content-type' => 'application/pdf']);
    
    }
}
