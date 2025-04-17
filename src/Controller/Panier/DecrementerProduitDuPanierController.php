<?php

namespace App\Controller\Panier;

use App\Repository\ProduitRepository;
use App\Service\PanierService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class DecrementerProduitDuPanierController extends AbstractController
{
    public function __construct(
        protected ProduitRepository $produitRepository, 
        protected PanierService $panierService)
    {}

    #[Route('/panier-decrementer/{slug}', name: 'panier_decrementer')]
    public function decrementer(Request $request, $slug)
    {
        # je récupère ma session
        $maSession = $request->getSession();

        if(!$maSession)
        {
            return $this->redirectToRoute("app_logout");
        }

        $produit = $this->produitRepository->findOneBySlug([
            'slug' => $slug
        ]);

        if (!$produit) 
        {
           throw $this->createNotFoundException("Le produit $slug n'existe pas et ne peut pes être décrementé !");
        }

        $this->panierService->decrementer($slug);

        return $this->redirectToRoute("panier_afficher");
    }
}
