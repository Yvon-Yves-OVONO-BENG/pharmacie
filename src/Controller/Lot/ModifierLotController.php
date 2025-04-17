<?php

namespace App\Controller\Lot;

use DateTime;
use App\Form\LotType;
use App\Service\StrService;
use App\Repository\LotRepository;
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
#[Route('/lot')]
class ModifierLotController extends AbstractController
{
    public function __construct(
        protected StrService $strService,
        protected EntityManagerInterface $em,
        protected TranslatorInterface $translator,
        protected LotRepository $lotRepository,
    )
    {}

    #[Route('/modifier-lot/{slug}', name: 'modifier_lot')]
    public function modifierLot(Request $request, string $slug): Response
    {
        # je récupère ma session
        $maSession = $request->getSession();

        if(!$maSession)
        {
            return $this->redirectToRoute("app_logout");
        }
        
        #mes variables témoin pour afficher les sweetAlert
        $maSession->set('ajout', null);
        $maSession->set('misAjour', null);
        $maSession->set('suppression', null);
        
        #je récupère le lot à modifier
        $lot = $this->lotRepository->findOneBySlug([
            'slug' => $slug
        ]);

        #je crée mon formulaire et je le lie à mon instance
        $form = $this->createForm(LotType::class, $lot);

        #je demande à mon formulaire de récupérer les donnéesqui sont dans le POST avec la $request
        $form->handleRequest($request);

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

        #je teste si mon formulaire est soumis et valide
        if ($form->isSubmitted() && $form->isValid()) 
        {
            #je met le nom du lot en CAPITAL LETTER
            $lot->setReference($this->strService->strToUpper($lot->getReference()))
                    ->setSlug($slug.$id)
                    ->setEnregistreLeAt(new DateTime('now'))
                    ->setHeureAt(new DateTime('now'))
                    ->setPrixVente($lot->getPrixAchat() * $lot->getCoef())
            ;

            $produitsDuLot = $lot->getProduits();

            foreach ($produitsDuLot as $produit) 
            {
                $produit->setPrixVente($lot->getPrixAchat() * $lot->getCoef());

                $this->em->persist($produit);
            }

            # je prépare ma requête avec entityManager
            $this->em->persist($lot);

            #j'exécutebma requête
            $this->em->flush();

            #j'affiche le message de confirmation d'ajout
            $this->addFlash('info', $this->translator->trans('Lot mis à jour avec succès !'));

            #j'affecte 1 à ma variable pour afficher le message
            $maSession->set('misAjour', 1);
            
            #je retourne à la liste des lots
            return $this->redirectToRoute('afficher_lot', [ 'm' => 1 ]);
        }

        # j'affiche mon formulaire avec twig
        return $this->render('lot/ajouterLot.html.twig', [
            'licence' => 1,
            'slug' => $slug,
            'lot' => $lot,
            'modification' => 1,
            'formLot' => $form->createView(),
        ]);
    }
}
