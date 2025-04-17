<?php

namespace App\Controller\Panier;

use App\Service\PanierService;
use App\Repository\ProduitRepository;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Contracts\Translation\TranslatorInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * @IsGranted("ROLE_USER", message="Accès refusé. Espace reservé uniquement aux abonnés")
 *
 */
class AjoutProduitPanierCopieController extends AbstractController
{
    public function __construct(
        protected RequestStack $request,
        protected PanierService $panierService, 
        protected TranslatorInterface $translator,
        protected ProduitRepository $produitRepository, 
        )
    {}

    #[Route('/ajout-produit-panier-copie/{slug}/{position}/{qte}', name: 'ajout_produit_panier_copie')]
    public function ajoutProduitPanierCopie(Request $request, string $slug = null, string $position = null, $qte = 0)
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
        
        if ($request->request->has('qte') && $request->request->has('ajoutPanier')) 
        {
            // 0. Sécurisation : est-ce que le produit existe ?
            $produit = $this->produitRepository->findOneBySlug([
                'slug' => $slug
            ]); 
            
            #si le produit n'existe pas
            if (!$produit) 
            {
                $kit = $this->produitRepository->findOneBySlug([
                    'slug' => $slug
                ]);
            
            }
            
            $this->panierService->ajout($slug, $request->request->get('qte'));

            if ($produit) 
            {
                $this->addFlash('info', $this->translator->trans('Produit ajouté dans le panier avec succès !'));
            } 
            else 
            {
                $this->addFlash('info', $this->translator->trans('Kit ajouté dans le panier avec succès !'));
            }
        } 
        
       #j'affecte 1 à ma variable pour afficher le message
       $maSession->set('ajout', 1);
            
       if ($position == 'panier') 
       {
            #je retourne au panier
            return $this->redirectToRoute('panier_afficher');
       } 
       elseif($position == 'listeProduit')
       {
            #je retourne à la liste des produits
            return $this->redirectToRoute('afficher_produit', [ 'p' => 1 ]);
       }
       elseif($position == 'listeKit')
       {
            #je retourne à la liste des kits
            return $this->redirectToRoute('liste_kit', [ 'm' => 1 ]);
       }

        // return new JsonResponse(['success' => true ]);
       
    }

}