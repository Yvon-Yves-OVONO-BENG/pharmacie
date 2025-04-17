<?php

namespace App\Controller\Produit;

use App\Form\ProduitType;
use App\Service\StrService;
use App\Entity\ConstantsClass;
use App\Repository\ProduitRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;


/**
 * @IsGranted("ROLE_USER", message="Accès refusé. Espace reservé uniquement aux abonnés")
 *
 */
#[Route('/produit')]
class ModifierProduitController extends AbstractController
{
    public function __construct(
        protected StrService $strService,
        protected EntityManagerInterface $em,
        protected TranslatorInterface $translator,
        private ParameterBagInterface $parametres,
        protected ProduitRepository $produitRepository,
    )
    {}

    #[Route('/modifier-produit/{slug}', name: 'modifier_produit')]
    public function modifierProduit(Request $request, string $slug): Response
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
        
        

        #je récupère le produit à modifier
        $produit = $this->produitRepository->findOneBySlug([
            'slug' => $slug
        ]);

        
        $produit->setPhoto($produit->getPhoto());
        
        #je crée mon formulaire et je le lie à mon instance
        $form = $this->createForm(ProduitType::class, $produit);

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

            //////j'extrait le dernier produit de la table
            $derniereProduit = $this->produitRepository->findBy([],['id' => 'DESC'],1,0);

            /////je récupère l'id du dernier produit
            $id = $derniereProduit[0]->getId();

            #je met le nom du produit en CAPITAL LETTER
            $produit->setLibelle($this->strService->strToUpper($produit->getLibelle()))
                ->setSlug($slug.$id)
                ->setPrixVente($produit->getLot()->getPrixVente());
            
            
            # je prépare ma requête avec entityManager
            $this->em->persist($produit)
            ;

            #j'exécutebma requête
            $this->em->flush();

            #je rénitialise mon slug
            $slug = "";

            #j'affiche le message de confirmation d'ajout
            $this->addFlash('info', $this->translator->trans('Produit mise à jour avec succès !'));

            #j'affecte 1 à ma variable pour afficher le message
            $maSession->set('ajout', 1);
            
            
            
            #je retourne à la liste des produits
            return $this->redirectToRoute('afficher_produit', [ 'm' => 1 ]);

        }


        # j'affiche mon formulaire avec twig
        return $this->render('produit/ajouterProduit.html.twig', [
            'licence' => 1,
            'slug' => $slug,
            'produit' => $produit,
            'formProduit' => $form->createView(),
        ]);
    }

}
