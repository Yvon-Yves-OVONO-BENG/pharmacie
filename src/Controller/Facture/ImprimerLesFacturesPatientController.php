<?php

namespace App\Controller\Facture;

use App\Entity\ConstantsClass;
use App\Repository\EtatFactureRepository;
use App\Repository\FactureRepository;
use App\Repository\ModePaiementRepository;
use App\Repository\PatientRepository;
use App\Service\ImpressionDesFactureService;
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
class ImprimerLesFacturesPatientController extends AbstractController
{
    public function __construct(
        protected FactureRepository $factureRepository,
        protected PatientRepository $patientRepository,
        protected EtatFactureRepository $etatFactureRepository,
        protected ModePaiementRepository $modePaiementRepository,
        protected ImpressionDesFactureService $impressionDesFactureService, 
        )
    {}
    
    #[Route('/imprimer-les-factures-patient/{codePatient}', name: 'imprimer_les_factures_patient')]
    public function imprimerFacture(Request $request, $codePatient): Response
    {
        # je récupère ma session
        $maSession = $request->getSession();

        if(!$maSession)
        {
            return $this->redirectToRoute("app_logout");
        }

        #je récupère le patient dont je veux imprimer les prise en charges
        $patient = $this->patientRepository->findOneByCode([
            'code' => $codePatient
        ]);
        
        $etatFacture = $this->etatFactureRepository->findOneByEtatFacture([
            'etatFacture' => ConstantsClass::NON_SOLDE
        ]);

        $modePaiement = $this->modePaiementRepository->findOneByModePaiement([
            'modePaiement' => ConstantsClass::PRIS_EN_CHARGE
        ]);


        $factures = $this->factureRepository->findBy([
            'patient' => $patient,
            'etatFacture' => $etatFacture,
            'modePaiement' => $modePaiement,
            'annulee' => 0
            ]);

        $pdf = $this->impressionDesFactureService->impressionDesFactures($factures);
    
        return new Response($pdf->Output(utf8_decode("Les factures de ".$patient->getNom()), "I"), 200, ['content-type' => 'application/pdf']);

    }
}
