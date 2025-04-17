<?php

namespace App\Controller\Commande;

use App\Repository\CommandeRepository;
use App\Service\ImpressionCommandeService;
use App\Service\ImpressionListeDesCommandesService;
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
#[Route('/commande')]
class ImprimerCommandeController extends AbstractController
{
    public function __construct(
        protected CommandeRepository $commandeRepository,
        protected ImpressionCommandeService $impressionCommandeService, 
        protected ImpressionListeDesCommandesService $impressionListeDesCommandesService, 
        )
    {}

    #[Route('/imprimer-commande/{slug}', name: 'imprimer_commande')]
    public function imprimerCommande(Request $request, string $slug = null): Response
    {
        # je récupère ma session
        $maSession = $request->getSession();

        if(!$maSession)
        {
            return $this->redirectToRoute("app_logout");
        }

        if ($slug) 
        {
            $commande = $this->commandeRepository->findOneBySlug([
                'slug' => $slug
                ]);

            $pdf = $this->impressionCommandeService->impressionCommande($commande);
        } 
        else 
        {
            $commandes = $this->commandeRepository->findAll();
           
            $pdf = $this->impressionListeDesCommandesService->impressionListeDesCommandes($commandes);
        }
        
        
        return new Response($pdf->Output(utf8_decode("Commande"), "I"), 200, ['content-type' => 'application/pdf']);

    }
}
