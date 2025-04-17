<?php

namespace App\Controller\Facture;

use App\Repository\FactureRepository;
use App\Repository\LigneDeFactureRepository;
use App\Repository\PatientRepository;
use App\Service\ImpressionDesFactureService;
use App\Service\ImpressionFactureService;
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
class ImprimerFactureController extends AbstractController
{
    public function __construct(
        protected FactureRepository $factureRepository,
        protected PatientRepository $patientRepository,
        protected ImpressionFactureService $impressionFactureService,
        protected LigneDeFactureRepository $ligneDeFactureRepository, 
        protected ImpressionDesFactureService $impressionDesFactureService, 
        )
    {}
    
    #[Route('/imprimer-facture/{slug}', name: 'imprimer_facture')]
    public function imprimerFacture(Request $request, $slug): Response
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

        $detailsFacture = $this->ligneDeFactureRepository->findBy([
            'facture' => $facture
        ]);
        
        $pdf = $this->impressionFactureService->impressionFacture($facture, $detailsFacture);
    
        return new Response($pdf->Output(utf8_decode("Facture de ".$facture->getReference()), "I"), 200, ['content-type' => 'application/pdf']);

    }
}
