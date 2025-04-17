<?php

namespace App\Controller\Profil;

use App\Service\SessionService;
use App\Form\ModifierMotDePasseType;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @IsGranted("ROLE_USER", message="Accès refusé. Espace reservé uniquement aux abonnés")
 *
 */
#[Route('/profil')]
class ModifierMotDePasseController extends AbstractController
{
    public function __construct(
        protected EntityManagerInterface $em,
        protected UserRepository $userRepository,
        protected SessionService $sessionService,
        protected TranslatorInterface $translator,
        protected UserPasswordHasherInterface $userPasswordHasher,
    )
    {}

    #[Route('/modifier-mot-de-passe', name: 'modifier_mot_de_passe')]
    public function modifierMotDePasse(Request $request): Response
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
         *@var User
         */

        $user = $this->getUser();

        $user = $this->userRepository->find($user->getId());

        $form = $this->createForm(ModifierMotDePasseType::class, $user);
        $form->handleRequest($request);
        
        if($form->isSubmitted() && $form->isValid())
        {
            $user->setPassword(
                $this->userPasswordHasher->hashPassword(
                    $user,
                    $user->getPassword()
                )
            );

            $this->em->flush();

            $this->addFlash('info', $this->translator->trans('Mot de passe modifié avec succès !'));

            #j'affecte 1 à ma variable pour afficher le message
            $maSession->set('ajout', 1);
            
            

            return $this->redirectToRoute('afficher_profil', ['m' => 1 ]);
        }


        return $this->render('profil/modifier_mot_de_passe.html.twig', [
            'licence' => 1,
            'motDePasse' => 1,
            'userForm' => $form->createView(),
        ]);
    }
}
