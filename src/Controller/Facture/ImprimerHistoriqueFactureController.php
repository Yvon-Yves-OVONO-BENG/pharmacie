<?php

namespace App\Controller\Facture;

use App\Entity\ConstantsClass;
use App\Repository\FactureRepository;
use App\Service\ImpressionHistoriqueService;
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
class ImprimerHistoriqueFactureController extends AbstractController
{
    public function __construct(
        protected ImpressionHistoriqueService $impressionHistoriqueService, 
        protected FactureRepository $factureRepository)
    {
        
    }
    
    #[Route('/imprimer-historique-facture/{typeUser}', name: 'imprimer_historique_facture')]
    public function imprimerHistoriqueFacture(Request $request, $typeUser): Response
    {
        # je récupère ma session
        $maSession = $request->getSession();

        if(!$maSession)
        {
            return $this->redirectToRoute("app_logout");
        }

        $user = $this->getUser();
        
        $role = $user->getRoles();
        
        if ($user && in_array(ConstantsClass::ROLE_ADMINISTRATEUR, $role)) 
        {  
            $factures = $this->factureRepository->findBy([
                'user' => $user,
                'annulee' => 0
            ]);

            $pdf = $this->impressionHistoriqueService->impressionHistorique($factures, $user, $typeUser);
            return new Response($pdf->Output(utf8_decode("Historique facture ", "I")), 200, ['content-type' => 'application/pdf']);

        }elseif ($user && in_array(ConstantsClass::ROLE_ADMINISTRATEUR, $role)) 
        {
            $factures = $this->factureRepository->findAll();

            $pdf = $this->impressionHistoriqueService->impressionHistorique($factures, $user, $typeUser);
            return new Response($pdf->Output(utf8_decode("Historique facture ", "I")), 200, ['content-type' => 'application/pdf']);

        }elseif ($user && in_array(ConstantsClass::ROLE_ADMINISTRATEUR, $role)) 
        {
            $factures = $this->factureRepository->findAll();

            $pdf = $this->impressionHistoriqueService->impressionHistorique($factures, $user, $typeUser);
            return new Response($pdf->Output(utf8_decode("Historique facture ", "I")), 200, ['content-type' => 'application/pdf']);
        }
    }
}
