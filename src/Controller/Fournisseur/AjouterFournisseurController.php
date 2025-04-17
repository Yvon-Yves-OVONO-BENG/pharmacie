<?php

namespace App\Controller\Fournisseur;

use App\Entity\Fournisseur;
use App\Service\StrService;
use App\Form\FournisseurType;
use App\Repository\FournisseurRepository;
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
#[Route('fournisseur')]
class AjouterFournisseurController extends AbstractController
{
    public function __construct(
        protected StrService $strService,
        protected EntityManagerInterface $em,
        protected TranslatorInterface $translator,
        protected FournisseurRepository $fournisseurRepository
    )
    {}

    #[Route('/ajouter-fournisseur', name: 'ajouter_fournisseur')]
    public function ajouterFournisseur(Request $request): Response
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

        #je déclare une nouvelle instace d'un fournisseur
        $fournisseur = new Fournisseur;

        #je crée mon formulaire et je le lie à mon instance
        $form = $this->createForm(FournisseurType::class, $fournisseur);

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

            //////j'extrait le dernier fournisseur de la table
            $dernierFournisseur = $this->fournisseurRepository->findBy([],['id' => 'DESC'],1,0);

            if(!$dernierFournisseur)
            {
                $id = 1;
            }
            else
            {
                /////je récupère l'id de la dernière facture
                $id = $dernierFournisseur[0]->getId();

            }

            #je met le nom du fournisseur en CAPITAL LETTER
            $fournisseur->setFournisseur($this->strService->strToUpper($fournisseur->getFournisseur()))
                    ->setSlug($slug.$id)
            ;
            
            # je prépare ma requête avec entityManager
            $this->em->persist($fournisseur);

            #j'exécutebma requête
            $this->em->flush();

            #j'affiche le message de confirmation d'ajout
            $this->addFlash('info', $this->translator->trans('Fournisseur ajouté avec succès !'));

            #j'affecte 1 à ma variable pour afficher le message
            $maSession->set('ajout', 1);
            
            
            
            #je déclare une nouvelle instace d'une fournisseur
            $fournisseur = new Fournisseur;

            #je crée mon formulaire et je le lie à mon instance
            $form = $this->createForm(FournisseurType::class, $fournisseur);
            
        }

        #je rénitialise mon slug
        $slug = 0;

        return $this->render('fournisseur/ajouterFournisseur.html.twig', [
            'licence' => 1,
            'slug' => $slug,
            'formFournisseur' => $form->createView(),
        ]);
    }
}
