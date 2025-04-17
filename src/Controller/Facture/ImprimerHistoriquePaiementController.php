<?php

namespace App\Controller\Facture;

use App\Repository\FactureRepository;
use App\Repository\HistoriquePaiementRepository;
use App\Repository\PatientRepository;
use App\Service\ImpressionFactureService;
use App\Service\ImpressionHistoriquePaiementService;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;

/**
 * @IsGranted("ROLE_USER", message="Accès refusé. Espace reservé uniquement aux abonnés")
 *
 */
#[Route('/facture')]
class ImprimerHistoriquePaiementController extends AbstractController
{
    public function __construct(
        protected FactureRepository $factureRepository,
        protected PatientRepository $patientRepository,
        protected ImpressionFactureService $impressionFactureService,
        protected HistoriquePaiementRepository $historiquePaiementRepository, 
        protected ImpressionHistoriquePaiementService $impressionHistoriquePaiementService, 
        )
    {}
    
    #[Route('/imprimer-historique-paiement/{slug}', name: 'imprimer_historique_paiement')]
    public function ImprimerHistoriquePaiement(Request $request, $slug): Response
    {
        # je récupère ma session
        $maSession = $request->getSession();

        if(!$maSession)
        {
            return $this->redirectToRoute("app_logout");
        }

        $facture = $this->factureRepository->findOneBySlug([
            'slug' => $slug
            ]);

        $historiqueFacture = $this->historiquePaiementRepository->findBy([
            'facture' => $facture
        ]);
        
        $pdf = $this->impressionHistoriquePaiementService->impressionHistoriquePaiement($facture, $historiqueFacture);
    
        return new Response($pdf->Output(utf8_decode("Historique de paiement de la facture ".$facture->getReference()), "I"), 200, ['content-type' => 'application/pdf']);

    }
}
