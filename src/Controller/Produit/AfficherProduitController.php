<?php

namespace App\Controller\Produit;

use App\Entity\ConstantsClass;
use App\Repository\ProduitRepository;
use App\Service\PanierService;
use DateTime;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

/**
 * @IsGranted("ROLE_USER", message="Accès refusé. Espace reservé uniquement aux abonnés")
 *
 */
#[Route('produit')]

class AfficherProduitController extends AbstractController
{
    public function __construct(
        protected PanierService $panierService,
        protected ProduitRepository $produitRepository,
    )
    {}

    #[Route('/afficher-produit/{a<[0-1]{1}>}/{m<[0-1]{1}>}/{s<[0-1]{1}>}/{p<[0-1]{1}>}', name: 'afficher_produit')]
    public function afficherProduit(Request $request, int $a = 0, int $m = 0, int $s = 0, int $p = 0): Response
    {
        # je récupère ma session
        $maSession = $request->getSession();

        if(!$maSession)
        {
            return $this->redirectToRoute("app_logout");
        }
        
        $nombreProduits = count($this->panierService->getDetailsPanierProduits($request));
        $totalApayer = $this->panierService->getTotal($request);

        $maSession->set('nombreProduits', $nombreProduits);
        $maSession->set('totalApayer', $totalApayer);

        if ($a == 1 || $m == 0 || $s == 0) 
        {
            #mes variables témoin pour afficher les sweetAlert
            $maSession->set('ajout', null);
            $maSession->set('misAjour', null);
            $maSession->set('suppression', null);
            
        }

        #je teste si le témoin n'est pas vide pour savoir s'il vient de la mise à jour
        if ($m == 1) 
        {
            #mes variables témoin pour afficher les sweetAlert
            $maSession->set('ajout', null);
            $maSession->set('misAjour', 1);
            $maSession->set('suppression', null);
            
        }

        #je teste si le témoin n'est pas vide pour savoir s'il vient de la mise à jour
        if ($p == 1) 
        {
            #mes variables témoin pour afficher les sweetAlert
            $maSession->set('ajout', 1);
            $maSession->set('misAjour', null);
            $maSession->set('suppression', null);
            
        }

        #je teste si le témoin n'est pas vide pour savoir s'il vient de la suppression
        if ($s == 1) 
        {
            #mes variables témoin pour afficher les sweetAlert
            $maSession->set('ajout', null);
            $maSession->set('misAjour', null);
            $maSession->set('suppression', 1);
            
        }
        
        #date du jour
        $aujourdhui = new DateTime('now');
        
        #je récupère tous les produits
        if ($this->getUser() && in_array(ConstantsClass::ROLE_CAISSIERE, $this->getUser()->getRoles())) 
        {
            $produits = $this->produitRepository->findBy([
                'kit' => 0,
                'supprime' => 0,
                'retire' => 0,
            ], ['libelle' => 'ASC' ]);
        } 
        else 
        {
            $produits = $this->produitRepository->findBy([
                'kit' => 0,
                'supprime' => 0,
            ], ['libelle' => 'ASC' ]);
        }
        
        
        #j'envoie mon tableau des produits à mon rendu twig pour affichage
        return $this->render('produit/afficherProduit.html.twig', [
            'licence' => 1,
            'seuil' => 0,
            'aujourdhui' => $aujourdhui,
            'produits' => $produits,
        ]);
    }
}
