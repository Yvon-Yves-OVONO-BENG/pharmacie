<?php

namespace App\Controller\Profil;

use App\Entity\ReponseQuestion;
use App\Form\MotDePasseOublieType;
use App\Form\ReponseQuestionType;
use App\Repository\QuestionSecreteRepository;
use App\Repository\ReponseQuestionRepository;
use App\Repository\UserRepository;
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
class MotDePasseOublieController extends AbstractController
{
    public function __construct(
        protected EntityManagerInterface $em,
        protected UserRepository $userRepository,
        protected TranslatorInterface $translator,
        private ParameterBagInterface $parametres,
        protected ReponseQuestionRepository $reponseQuestionRepository,
        protected QuestionSecreteRepository $questionSecreteRepository,
    )
    {}

    #[Route('/mot-de-passe-oublie', name: 'mot_de_passe_oublie')]
    public function motDePasseOublie(Request $request): Response
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

        #je récupère les utilisateurs
        $users = $this->userRepository->findAll();

        #je r"cupère les questions secrètes
        $questionSecretes = $this->questionSecreteRepository->findAll();

        if ($request->request->has('envoyer') && $request->request->has('questionSecreteId') && $request->request->has('userId') && $request->request->has('reponse')) 
        {
            $questionReponse = $this->reponseQuestionRepository->findOneBy([
                'reponse' => $request->request->get('reponse'),
                'user' => $this->userRepository->find($request->request->get('userId')),
                'questionSecrete' => $this->questionSecreteRepository->find($request->request->get('questionSecreteId')),
            ]);

            if($questionReponse)
            {
                #j'affiche le message de confirmation d'ajout
                $this->addFlash('info', $this->translator->trans("Bonne réponse ! Réinitialiser votre mot de passe ! "));
            
                return $this->redirectToRoute('reinitialiser_mot_de_passe', ['slugUser' => $this->userRepository->find($request->request->get('userId'))->getSlug()  ]);
                
            }
            else
            {
                #j'affiche le message de confirmation d'ajout
                $this->addFlash('info', $this->translator->trans("L'une des trois informations n'est pas correcte "));
            
                return $this->redirectToRoute('mot_de_passe_oublie');
            }
        }


        return $this->render('profil/motDePasseOublie.html.twig', [
            'licence' => 1,
            'motDePasse' => 0,
            'users' => $users,
            'questionSecretes' => $questionSecretes,
        ]);
    }

}
