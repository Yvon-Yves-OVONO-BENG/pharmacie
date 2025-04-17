<?php

namespace App\Controller\Produit;

use App\Repository\ProduitRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Contracts\Translation\TranslatorInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

/**
 * @IsGranted("ROLE_USER", message="Accès refusé. Espace reservé uniquement aux abonnés")
 *
 */
#[Route('/produit')]
class RetireProduitController extends AbstractController
{
    public function __construct(
        protected EntityManagerInterface $em,
        protected TranslatorInterface $translator,
        protected ProduitRepository $produitRepository
    )
    {}

    #[Route('/retirer-produit', name: 'retirer_produit', methods: 'POST')]
    public function retirerProduit(Request $request): JsonResponse
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
        
        $produitId = (int)$request->request->get('produit_id');
        
        $produit = $this->produitRepository->find($produitId);
        
        if ($produit->isRetire() == 0) 
        {
            $produit->setRetire(1);
        } else {
            $produit->setRetire(0);
        }
        
        #je prépare ma requête à la suppression
        $this->em->persist($produit);

        #j'exécute ma requête
        $this->em->flush();

        #je retourne à la liste des catégories
        return new JsonResponse(['success' => true, 'retire' => $produit->isRetire() ]);
    }
}
