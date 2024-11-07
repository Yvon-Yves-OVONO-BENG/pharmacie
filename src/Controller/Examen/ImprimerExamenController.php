<?php

namespace App\Controller\Examen;

use App\Repository\ProduitRepository;
use App\Service\ImpressionDesExamensService;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

/**
 * @IsGranted("ROLE_USER", message="Accès refusé. Espace reservé uniquement aux abonnés")
 *
 */
#[Route('/examen')]
class ImprimerExamenController extends AbstractController
{
    public function __construct(
        protected ImpressionDesExamensService $impressionDesExamensService,
        protected ProduitRepository $produitRepository)
    {}

    #[Route('/imprimer-examen', name: 'imprimer_examen')]
    public function imprimerExamen(): Response
    {
        $examens = $this->produitRepository->findBy([
            'examen' => 1
            ]);
        
        $pdf = $this->impressionDesExamensService->impressionDesExamens($examens);
        return new Response($pdf->Output(utf8_decode("Les Examens "), "I"), 200, ['content-type' => 'application/pdf']);
    
    }
}
