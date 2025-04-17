<?php

namespace App\Controller\Societe;

use App\Entity\Societe;
use App\Form\SocieteType;
use App\Service\StrService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @IsGranted("ROLE_USER", message="Accès refusé. Espace reservé uniquement aux abonnés")
 *
 */
#[Route('societe')]
class AjouterSocieteController extends AbstractController
{
    public function __construct(
        protected StrService $strService,
        protected EntityManagerInterface $em,
        protected TranslatorInterface $translator,
    )
    {}

    #[Route('/ajouter-societe', name: 'ajouter_societe')]
    public function ajouterSociete(Request $request): Response
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

        

        $slug = 0;

        #je déclare une nouvelle instace d'une societe
        $societe = new Societe;

        #je crée mon formulaire et je le lie à mon instance
        $form = $this->createForm(SocieteType::class, $societe);

        #je demande à mon formulaire de récupérer les donnéesqui sont dans le POST avec la $request
        $form->handleRequest($request);
        
        #je teste si mon formulaire est soumis et valide
        if ($form->isSubmitted() && $form->isValid()) 
        {
            #je fabrique mon slug
            $characts    = 'abcdefghijklmnopqrstuvwxyz#{};()';
            $characts   .= 'ABCDEFGHIJKLMNOPQRSTUVWXYZ#{};()';	
            $characts   .= '1234567890'; 
            $slug      = ''; 
    
            for($i=0;$i < 11;$i++) 
            { 
                $slug .= substr($characts,rand()%(strlen($characts)),1); 
            }

            #je met le nom de la societe en CAPITAL LETTER
            $societe->setSociete($this->strService->strToUpper($societe->getSociete()))
                    ->setSlug($slug)
            ;
            
            # je prépare ma requête avec entityManager
            $this->em->persist($societe);

            #j'exécutebma requête
            $this->em->flush();

            #j'affiche le message de confirmation d'ajout
            $this->addFlash('info', $this->translator->trans('Societe ajoutée avec succès !'));

            #j'affecte 1 à ma variable pour afficher le message
            $maSession->set('ajout', 1);
            
            
            
            #je déclare une nouvelle instace d'une societe
            $societe = new Societe;

            #je crée mon formulaire et je le lie à mon instance
            $form = $this->createForm(SocieteType::class, $societe);
            
        }

        #je rénitialise mon slug
        $slug = 0;

        return $this->render('societe/ajouterSociete.html.twig', [
            'licence' => 1,
            'slug' => $slug,
            'formSociete' => $form->createView(),
        ]);
    }
}
