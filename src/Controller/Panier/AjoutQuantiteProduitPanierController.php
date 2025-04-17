<?php

namespace App\Controller\Panier;

use App\Service\PanierService;
use App\Repository\KitRepository;
use App\Repository\ProduitRepository;
use App\Service\AjoutQuantiteProduitService;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Contracts\Translation\TranslatorInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;

/**
 * @IsGranted("ROLE_USER", message="Accès refusé. Espace reservé uniquement aux abonnés")
 *
 */
class AjoutQuantiteProduitPanierController extends AbstractController
{
    public function __construct(
        protected RequestStack $request,
        protected PanierService $panierService, 
        protected TranslatorInterface $translator, 
        protected AjoutQuantiteProduitService $ajoutQuantiteProduitService,
        )
    {}

    #[Route('/ajout-quantite-produit-panier', name: 'ajout_quantite_produit_panier')]
    public function ajoutQuantiteProduitPanier(Request $request)
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
        
        if ($request->request->has("mettreAjour")) 
        {
            $detailsPanier = $this-> panierService->getDetailsPanierProduits($request);
            
            $this->ajoutQuantiteProduitService->ajoutQuantite($request);
        }

       #j'affecte 1 à ma variable pour afficher le message
       $maSession->set('ajout', 1);

       return $this->redirectToRoute('panier_afficher');
       
       
    }

}