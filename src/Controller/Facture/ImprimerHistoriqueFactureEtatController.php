<?php

namespace App\Controller\Facture;

use App\Repository\FactureRepository;
use App\Repository\EtatFactureRepository;
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
class ImprimerHistoriqueFactureEtatController extends AbstractController
{
    public function __construct(
        protected ImpressionHistoriqueService $impressionHistoriqueService, 
        protected FactureRepository $commandeRepository, 
        protected UserRepository $userRepository, 
        protected EtatFactureRepository $etatFactureRepository)
    {
        
    }
    
    #[Route('/imprimer-historique-commande-etat', name: 'imprimer_historique_commande_etat')]
    public function imprimerHistoriqueFactureEtat(Request $request): Response
    {
        # je récupère ma session
        $maSession = $request->getSession();

        if(!$maSession)
        {
            return $this->redirectToRoute("app_logout");
        }
        
        $etatFacture = $request->request->get('etatFacture');
       
        $etatFacture = $this->etatFactureRepository->find($etatFacture);
        
        $commandes = $this->commandeRepository->findBy([
            'etatFacture' => $etatFacture
        ]);

       
        $pdf = $this->impressionHistoriqueService->impressionHistoriqueEtat($commandes);
        return new Response($pdf->Output(utf8_decode("Historique commande état"), "I"), 200, ['content-type' => 'application/pdf']);

        
    }
}
