<?php

namespace App\Controller\Kit;

use App\Repository\ProduitRepository;
use App\Service\ImpressionDesKitsService;
use App\Service\ImpressionKitService;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;

/**
 * @IsGranted("ROLE_USER", message="Accès refusé. Espace reservé uniquement aux abonnés")
 *
 */
#[Route('/kit')]
class ImprimerKitController extends AbstractController
{
    public function __construct(
        protected ImpressionKitService $impressionKitService,
        protected ImpressionDesKitsService $impressionDesKitsService,
        protected ProduitRepository $produitRepository)
    {}

    #[Route('/imprimer-kit/{slug}', name: 'imprimer_kit')]
    public function imprimerKit(Request $request, string $slug = null): Response
    {
        # je récupère ma session
        $maSession = $request->getSession();

        if(!$maSession)
        {
            return $this->redirectToRoute("app_logout");
        }

        if ($slug != null) 
        {
            $kit = $this->produitRepository->findOneBySlug([
                'slug' => $slug
                ]);
            
            $pdf = $this->impressionKitService->impressionKit($kit);
            return new Response($pdf->Output(utf8_decode($kit->getLibelle()), "I"), 200, ['content-type' => 'application/pdf']);

        } 
        else 
        {
            $kits = $this->produitRepository->findBy([
                'kit' => 1
                ]);
            
            $pdf = $this->impressionDesKitsService->impressionDesKits($kits);
            return new Response($pdf->Output(utf8_decode("Liste des Kits "), "I"), 200, ['content-type' => 'application/pdf']);

        }
        
    }
}
