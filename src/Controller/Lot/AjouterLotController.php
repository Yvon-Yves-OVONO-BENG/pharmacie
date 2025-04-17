<?php

namespace App\Controller\Lot;

use App\Entity\Lot;
use App\Form\LotType;
use App\Repository\LotRepository;
use App\Service\StrService;
use DateTime;
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
#[Route('lot')]
class AjouterLotController extends AbstractController
{
    public function __construct(
        protected StrService $strService,
        protected LotRepository $lotRepository,
        protected EntityManagerInterface $em,
        protected TranslatorInterface $translator,
    )
    {}

    #[Route('/ajouter-lot', name: 'ajouter_lot')]
    public function ajouterLot(Request $request): Response
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

        #je déclare une nouvelle instace d'une Lot
        $lot = new Lot;
        
        #je crée mon formulaire et je le lie à mon instance
        $form = $this->createForm(LotType::class, $lot);
        
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
            $dernierLot= $this->lotRepository->findBy([],['id' => 'DESC'],1,0);

            if(!$dernierLot)
            {
                $id = 1;
            }
            else
            {
            /////je récupère l'id de la dernière facture
            $id = $dernierLot[0]->getId();

            }
        
            #je met le nom du lot en CAPITAL LETTER
            $lot->setReference($this->strService->strToUpper($lot->getReference()))
                ->setSlug($slug.$id)
                ->setPrixVente($lot->getPrixAchat() * $lot->getCoef())
                ->setEnregistreLeAt(new DateTime('now'))
                ->setHeureAt(new DateTime('now'))
                ->setEnregistrePar($this->getUser())
            ;
            
            # je prépare ma requête avec entityManager
            $this->em->persist($lot);

            #j'exécutebma requête
            $this->em->flush();

            #j'affiche le message de confirmation d'ajout
            $this->addFlash('info', $this->translator->trans('Lot ajoutée avec succès !'));

            #j'affecte 1 à ma variable pour afficher le message
            $maSession->set('ajout', 1);
            
            
            
            #je déclare une nouvelle instace d'un lot
            $lot = new Lot;

            #je crée mon formulaire et je le lie à mon instance
            $form = $this->createForm(LotType::class, $lot);
            
        }

        #je rénitialise mon slug
        $slug = 0;

        return $this->render('lot/ajouterLot.html.twig', [
            'licence' => 1,
            'modification' => 0,
            'slug' => $slug,
            'formLot' => $form->createView(),
        ]);
    }
}
