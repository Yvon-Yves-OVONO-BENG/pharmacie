<?php

namespace App\Controller\Patient;

use App\Form\PatientType;
use App\Service\StrService;
use App\Repository\PatientRepository;
use Doctrine\ORM\EntityManagerInterface;
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
#[Route('/patient')]
class ModifierPatientController extends AbstractController
{
    public function __construct(
        protected StrService $strService,
        protected EntityManagerInterface $em,
        protected TranslatorInterface $translator,
        protected PatientRepository $patientRepository,
    )
    {}

    #[Route('/modifier-patient/{code}', name: 'modifier_patient')]
    public function modifierPatient(Request $request, string $code): Response
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
        
        

        #je récupère la patient à modifier
        $patient = $this->patientRepository->findOneByCode([
            'code' => $code
        ]);

        #je crée mon formulaire et je le lie à mon instance
        $form = $this->createForm(PatientType::class, $patient);

        #je demande à mon formulaire de récupérer les donnéesqui sont dans le POST avec la $request
        $form->handleRequest($request);

        #je teste si mon formulaire est soumis et valide
        if ($form->isSubmitted() && $form->isValid()) 
        {
            #je met le nom de la patient en CAPITAL LETTER
            $patient->setNom($this->strService->strToUpper($patient->getNom()))
            ->setTermine(0);

            # je prépare ma requête avec entityManager
            $this->em->persist($patient);

            #j'exécutebma requête
            $this->em->flush();

            #j'affiche le message de confirmation d'ajout
            $this->addFlash('info', $this->translator->trans('Patient mise à jour avec succès !'));

            #j'affecte 1 à ma variable pour afficher le message
            $maSession->set('ajout', 1);
            
            

            #je retourne à la liste des categories
            return $this->redirectToRoute('afficher_patient', [ 'm' => 1 ]);
        }

        # j'affiche mon formulaire avec twig
        return $this->render('patient/ajouterPatient.html.twig', [
            'licence' => 1,
            'code' => $code,
            'patient' => $patient,
            'formPatient' => $form->createView(),
        ]);
    }
}
