<?php

namespace App\Controller\Kit;

use App\Repository\ProduitRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

/**
 * @IsGranted("ROLE_USER", message="Accès refusé. Espace reservé uniquement aux abonnés")
 *
 */
#[Route('/kit')]
class DetailsKitController extends AbstractController
{
    public function __construct(
        protected ProduitRepository $produitRepository,
    )
    {}

    #[Route('/details-kit/{slug}', name: 'details_kit')]
    public function detailsKit(Request $request, string $slug): Response
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
        
        #je récupère la kit dont je veux modifier
        $kit = $this->produitRepository->findOneBySlug([
            'slug' => $slug
        ]);
        
        
        return $this->render('kit/detailsKit.html.twig', [
            'licence' => 1,
            'slug' => $slug,
            'kit' => $kit,
        ]);
    }
}
