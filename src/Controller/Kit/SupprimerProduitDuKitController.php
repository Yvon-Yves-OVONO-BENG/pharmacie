<?php

namespace App\Controller\Kit;

use App\Repository\ProduitRepository;
use App\Repository\LigneDeKitRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Translation\TranslatorInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

/**
 * @IsGranted("ROLE_USER", message="Accès refusé. Espace reservé uniquement aux abonnés")
 *
 */
#[Route('/kit')]
class SupprimerProduitDuKitController extends AbstractController
{
    public function __construct(
        protected EntityManagerInterface $em,
        protected TranslatorInterface $translator,
        protected LigneDeKitRepository $ligneDeKitRepository,
    )
    {}
    
    #[Route('/supprimer-produit-du-kit/{id}/{slug}', name: 'supprimer_produit-du_kit')]
    public function supprimerProduotDuKit(Request $request, $id, $slug): Response
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
        
        # je récupère la kit dont je veux modifier l'état
        $produit = $this->ligneDeKitRepository->find($id);

        #je prépare ma requete à la suppression
        $this->em->remove($produit);

        #j'exécute ma requete
        $this->em->flush();

        #j'affiche le message de confirmation
        $this->addFlash('info', $this->translator->trans('Produit supprimé avec succès !'));
            
        #j'affecte 1 à ma variable pour afficher le message
        $maSession->set('suppression', 1);
        
        #je redirige vers la liste des kits
        return $this->redirectToRoute('afficher_kit', ['slug' => $slug ]);
    }
}
