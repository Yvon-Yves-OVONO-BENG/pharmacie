<?php

namespace App\Controller\Lot;

use App\Repository\LotRepository;
use App\Service\ImpressionLotService;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;

/**
 * @IsGranted("ROLE_USER", message="Accès refusé. Espace reservé uniquement aux abonnés")
 *
 */
#[Route('/lot')]
class ImprimerLotController extends AbstractController
{
    public function __construct(
        protected LotRepository $lotRepository,
        protected ImpressionLotService $impressionLotService, 
        )
    {}

    #[Route('/imprimer-lot/{periode}', name: 'imprimer_lot')]
    public function imprimerLot(Request $request, $periode = 0): Response
    {
        # je récupère ma session
        $maSession = $request->getSession();

        if(!$maSession)
        {
            return $this->redirectToRoute("app_logout");
        }
        
        if ($periode == 1) 
        {
            if ($request->request->has('impressionLotPeriode')) 
            {
                #je récupère les date de DDEBUT et de FIN
                $dateDebut = date_create($request->request->get('dateDebut'));
                $dateFin = date_create($request->request->get('dateFin'));

                $lots = $this->lotRepository->lotPeriode($dateDebut, $dateFin);
                $pdf = $this->impressionLotService->impressionLot($lots, $dateDebut, $dateFin, $periode);
            } 
            else 
            {
                return $this->redirectToRoute('afficher_lot', ['a' => 0]);
            }
            
        } 
        else 
        {
            $lots = $this->lotRepository->findBy([], ['reference' => 'ASC' ]);
            $pdf = $this->impressionLotService->impressionLot($lots);
        }
        
        
        
        return new Response($pdf->Output(utf8_decode("Lots"), "I"), 200, ['content-type' => 'application/pdf']);

    }
}
