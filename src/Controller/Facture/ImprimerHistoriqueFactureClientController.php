<?php

namespace App\Controller\Facture;

use App\Repository\FactureRepository;
use App\Repository\UserRepository;
use App\Service\ImpressionHistoriqueService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

/**
 * @IsGranted("ROLE_USER", message="Accès refusé. Espace reservé uniquement aux abonnés")
 *
 */
#[Route('/facture')]
class ImprimerHistoriqueFactureClientController extends AbstractController
{
    public function __construct(
        protected ImpressionHistoriqueService $impressionHistoriqueService, 
        protected FactureRepository $commandeRepository, 
        protected UserRepository $userRepository)
    {
        
    }
    
    #[Route('/imprimer-historique-commande-client', name: 'imprimer_historique_commande_client')]
    public function imprimerHistoriqueFacture(Request $request): Response
    {
        # je récupère ma session
        $maSession = $request->getSession();

        if(!$maSession)
        {
            return $this->redirectToRoute("app_logout");
        }
        
        $client = $request->request->get('client');

        $user = $this->userRepository->find($client);
        
        $commandes = $this->commandeRepository->findBy([
            'user' => $client
        ]);

        $pdf = $this->impressionHistoriqueService->impressionHistoriqueClient($commandes, $user);
        return new Response($pdf->Output(utf8_decode("Historique commande de ".$user->getNom()), "I"), 200, ['content-type' => 'application/pdf']);

        
    }
}
