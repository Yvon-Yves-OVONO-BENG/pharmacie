<?php

namespace App\Controller\Facture;

use App\Entity\ConstantsClass;
use App\Repository\EtatFactureRepository;
use App\Repository\FactureRepository;
use App\Repository\LigneDeFactureRepository;
use App\Repository\PatientRepository;
use App\Repository\UserRepository;
use App\Service\ImpressionDesFactureService;
use App\Service\ImpressionLesFacturesService;
use DateTime;
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
class ImprimerLesFacturesController extends AbstractController
{
    public function __construct(
        protected UserRepository $userRepository,
        protected FactureRepository $factureRepository,
        protected PatientRepository $patientRepository,
        protected EtatFactureRepository $etatFactureRepository,
        protected ImpressionLesFacturesService $impressionLesFacturesService,
        protected LigneDeFactureRepository $ligneDeFactureRepository, 
        protected ImpressionDesFactureService $impressionDesFactureService, 
        )
    {}
    
    #[Route('/imprimer-les-factures/{aujourdhui}/{toutes}/{periode}', name: 'imprimer_les_factures')]
    public function imprimerFacture(Request $request, $aujourdhui = 0, $toutes = 0, $periode = 0): Response
    {
        # je récupère ma session
        $maSession = $request->getSession();

        if(!$maSession)
        {
            return $this->redirectToRoute("app_logout");
        }
            
        #mes variables témoin pour afficher les sweetAlert
        $maSession->set('ajout', null);
        $maSession->set('suppression', null);
        
        

        #je récupère l'utilisateur connecté
        /**
         * @var User
         */
        $user = $this->getUser();

        #si on veut imprimer les factures d'aujourd'hui
        if ($aujourdhui == 1) 
        {
            #si l'utilisateur connecté est une caissière
            if ($user && in_array(ConstantsClass::ROLE_CAISSIERE, $user->getRoles())) 
            {
                #je récupère ses factures du jour de la BD
                $factures = $this->factureRepository->findBy([
                    'dateFactureAt' => new DateTime('now'),
                    'caissiere' => $user,
                    'annulee' => 0
                    ]);
            } 
            #si ce n,'est pas une caissière
            else 
            {
                #je recupère toutes les factures du jour
                $factures = $this->factureRepository->findBy([
                    'dateFactureAt' => new DateTime('now'),
                    'annulee' => 0
                    ]);
            }
             
        } 
        #si on veut imprimer toutes les factures
        elseif($toutes == 1) 
        {
            #si l'utilisateur connecté est une caissière
            if ($user && in_array(ConstantsClass::ROLE_CAISSIERE, $user->getRoles())) 
            {
                #je récupère toutes ses factures
                $factures = $this->factureRepository->findBy([
                    'annulee' => 0,
                    'caissiere' => $user,
                    ]);
            }
            #si ce n'est pas une caissière
            else
            {
                #j'imprime toutes les factures
                $factures = $this->factureRepository->findBy([
                    'annulee' => 0,
                    ]);
            }
        }
        #si je veux imprimer les factures d'une période
        elseif ($periode == 1) 
        {
            #si la caissière est connectée
            if ($user && in_array(ConstantsClass::ROLE_CAISSIERE, $user->getRoles())) 
            {
                #si mon post a la variable "impressionFacturePeriode"
                if ($request->request->has('impressionFacturePeriode')) 
                {
                    #je récupère la caissière
                    $caissiere = $this->userRepository->find($user->getId());

                    #je récupère l'état de la facture si c'est définie
                    $etatFacture = $this->etatFactureRepository->find((int)$request->request->get('etatFacture'));
                    
                    #je récupère les date de DDEBUT et de FIN
                    $dateDebut = date_create($request->request->get('dateDebut'));
                    $dateFin = date_create($request->request->get('dateFin'));

                    #j'appelle ma requête
                    $factures = $this->factureRepository->facturePeriode($caissiere, $etatFacture, $dateDebut, $dateFin);
            

                }
            }
            #si ce n'est pas la caissière qui est connectée
            else
            {
                #j'envoie une caissière nulle
                $caissiere = null;

                #je récupère l'état de la facture
                $etatFacture = $this->etatFactureRepository->find((int)$request->request->get('etatFacture'));
                
                #je récupère les dates de DEBUT et de FIN
                // $dateDebut = date_create($request->request->get('dateDebut'));
                // $dateFin = date_create($request->request->get('dateFin'));
                $dateDebut = null;
                $dateFin = null;
                if ($request->request->get('dateDebut') && $request->request->get('dateFin')) 
                {
                    $dateDebut = $request->request->get('dateDebut');
                    $dateDebut = new DateTime($dateDebut);

                    $dateFin = $request->request->get('dateFin');
                    $dateFin = new DateTime($dateFin);
                }
               
                #j'envoie ces paramètres à ma fonction requête
                $factures = $this->factureRepository->facturePeriode($caissiere, $etatFacture, $dateDebut, $dateFin);
                
            }
        }
        
        #je fait appel à mon service d'impression
        $pdf = $this->impressionLesFacturesService->impressionLesFactures($factures);
        
        #je retourne on objet de type pdf pour impression
        return new Response($pdf->Output(utf8_decode("Les factures"), "I"), 200, ['content-type' => 'application/pdf']);

    }
}
