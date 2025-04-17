<?php

namespace App\Controller\Profil;

use App\Repository\ProfilRepository;
use App\Service\SessionService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

/**
 * @IsGranted("ROLE_USER", message="Accès refusé. Espace reservé uniquement aux abonnés")
 *
 */
#[Route('/profil')]
class AfficherProfilController extends AbstractController
{
    public function __construct(
        protected SessionService $sessionService,
        protected ProfilRepository $profilRepository,
    )
    {}

    #[Route('/afficher-profil/{m}', name: 'afficher_profil')]
    public function afficherProfil(Request $request, int $m = 0): Response
    {
        # je récupère ma session
        $maSession = $request->getSession();

        if(!$maSession)
        {
            return $this->redirectToRoute("app_logout");
        }

        #je teste si le témoin n'est pas vide pour savoir s'il vient de la mise à jour
        if ($m == 1) 
        {
            #mes variables témoin pour afficher les sweetAlert
            $maSession->set('ajout', 1);
            $maSession->set('suppression', null);
            
        }
        else
        {
            #mes variables témoin pour afficher les sweetAlert
            $maSession->set('ajout', null);
            $maSession->set('suppression', null);

        }
        
        #je récupère l'utilisateur connecté
        /**
         * @var User
         */
        $user = $this->getUser();

        $profil = $this->profilRepository->findOneByUser([
            'user' => $user
        ]);
       
        #j'envoie mon user à mon rendu twig
        return $this->render('profil/afficher_profil.html.twig', [
            'licence' => 1,
            'profil' => $profil
        ]);
    }
}
