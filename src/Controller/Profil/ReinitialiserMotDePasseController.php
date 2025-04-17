<?php

namespace App\Controller\Profil;

use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Translation\TranslatorInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

/**
 * @IsGranted("ROLE_USER", message="Accès refusé. Espace reservé uniquement aux abonnés")
 *
 */
#[Route('profil')]
class ReinitialiserMotDePasseController extends AbstractController
{
    public function __construct(
        protected EntityManagerInterface $em,
        protected UserRepository $userRepository,
        protected TranslatorInterface $translator,
        private ParameterBagInterface $parametres,
        protected UserPasswordHasherInterface $userPasswordHasher,
    )
    {}

    #[Route('/reinitialiser-mot-de-passe/{slugUser}', name: 'reinitialiser_mot_de_passe')]
    public function reinitialiserMotDePasseOublie(Request $request, $slugUser): Response
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

        $user = $this->userRepository->findOneBySlug([
            'slug' => $slugUser
        ]);

        if ($request->request->has('envoyer') && $request->request->has('motDePasse')) 
        {
            $user->setPassword(
                $this->userPasswordHasher->hashPassword(
                    $user,
                    $request->request->get('motDePasse')
                )
            );

            $this->em->persist($user);
            $this->em->flush();

            #j'affiche le message de confirmation d'ajout
            $this->addFlash('info', $this->translator->trans("Mot de passe réinitialisé avec succèss ! "));

            return $this->redirectToRoute('app_login');
            
        }


        return $this->render('profil/reinitialiser_mot_de_passe.html.twig', [
            'licence' => 1,
            'motDePasse' => 0,
            'slugUser' => $slugUser,
        ]);
    }

}
