<?php

namespace App\Controller\Profil;

use App\Entity\ReponseQuestion;
use App\Form\ReponseQuestionType;
use App\Repository\ReponseQuestionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Translation\TranslatorInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

/**
 * @IsGranted("ROLE_USER", message="Accès refusé. Espace reservé uniquement aux abonnés")
 *
 */
#[Route('profil')]
class ReponseQuestionController extends AbstractController
{
    public function __construct(
        protected EntityManagerInterface $em,
        protected TranslatorInterface $translator,
        private ParameterBagInterface $parametres,
        protected ReponseQuestionRepository $reponseQuestionRepository
    )
    {}

    #[Route('/reponse-question', name: 'reponse_question')]
    public function reponseQuestion(Request $request): Response
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

        $reponseQuestion = $this->reponseQuestionRepository->findOneBy([
            'user' => $this->getUser()
        ]);

        if (!$reponseQuestion) 
        {
            #je déclare une nouvelle instace d'un Produit
            $reponseQuestion = new ReponseQuestion;
        }

        #je crée mon formulaire et je le lie à mon instance
        $form = $this->createForm(ReponseQuestionType::class, $reponseQuestion);

        #je demande à mon formulaire de récupérer les donnéesqui sont dans le POST avec la $request
        $form->handleRequest($request);
        
        #je teste si mon formulaire est soumis et valide
        if ($form->isSubmitted() && $form->isValid()) 
        {
            #je met le nom du produit en CAPITAL LETTER
            $reponseQuestion->setUser($this->getUser());
            
            # je prépare ma requête avec entityManager
            $this->em->persist($reponseQuestion);

            #j'exécutebma requête
            $this->em->flush();

            #j'affiche le message de confirmation d'ajout
            $this->addFlash('info', $this->translator->trans('Question secrète enregistrée avec succès !'));

            #j'affecte 1 à ma variable pour afficher le message
            $maSession->set('ajout', 1);
            
            return $this->redirectToRoute('afficher_profil', ['m' => 1]);
        }

        return $this->render('profil/questionReponse.html.twig', [
            'licence' => 1,
            'formQuestionSecrete' => $form->createView(),
        ]);
    }

}
