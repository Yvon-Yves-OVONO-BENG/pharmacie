<?php

namespace App\Controller\Kit;

use App\Entity\ConstantsClass;
use App\Entity\Kit;
use App\Entity\Produit;
use App\Form\KitType;
use App\Form\ProduitType;
use App\Service\StrService;
use App\Repository\KitRepository;
use App\Repository\ProduitRepository;
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
#[Route('/kit')]
class AjouterKitController extends AbstractController
{
    public function __construct(
        protected StrService $strService,
        protected EntityManagerInterface $em,
        protected TranslatorInterface $translator,
        protected ProduitRepository $produitRepository
    )
    {}

    #[Route('/ajouter-kit', name: 'ajouter_kit')]
    public function ajouterKit(Request $request): Response
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
        
        #j'initialise le slug
        $slug = 0;

        #je céclare une nouvelle instance kit
        $kit = new Produit;

        #je lie mon formulaire à ma nouvelle instance
        $form = $this->createForm(KitType::class, $kit);

        #je demande à mon formulaire de gueter tout ce qui est dans le POST
        $form->handleRequest($request);

        #je construis le code pour la reference de la kit
        $characts    = 'abcdefghijklmnopqrstuvwxyz';
        $characts   .= 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $characts   .= '1234567890';
        $slug      = '';

        for ($i = 0; $i < 11; $i++) {
            $slug .= substr($characts, rand() % (strlen($characts)), 1);
        }

        //////j'extrait la dernière kit de la table
        $derniereProduit = $this->produitRepository->findBy([], ['id' => 'DESC'], 1, 0);

        if ($derniereProduit) 
        {
            /////je récupère l'id du dernier abonnement
            $id = $derniereProduit[0]->getId();
        } 
        else 
        {
            $id = 1;
        }

        if ($form->isSubmitted() && $form->isValid()) 
        {
            $prix = 0;
            $ligneDeKits = $kit->getProduitLigneDeKits();

            foreach ($ligneDeKits as $ligneDeKit) 
            {
                $ligneDeKit->setPrix($ligneDeKit->getProduit()->getPrixVente());
                $ligneDeKit->setTotal($ligneDeKit->getProduit()->getPrixVente() * $ligneDeKit->getQuantite());
                $prix += ($ligneDeKit->getProduit()->getPrixVente() * $ligneDeKit->getQuantite());

                $this->em->persist($ligneDeKit);
            }

            $kit->setKit($this->strService->strToUpper($kit->getLibelle()))
            ->setPrixVente($prix)
            ->setKit(1)
            ->setSupprime(0)
            ->setSlug($slug.$id)
            ->setPhoto(ConstantsClass::NOM_PRODUIT)
            ;

            
            #je prepare ma requete
            $this->em->persist($kit);

            #j'exécute ma requête
            $this->em->flush();

            #j'affiche le message de confirmation
            $this->addFlash('info', $this->translator->trans('Kit ajoutée avec succès !'));
            
            #j'affecte 1 à ma variable pour afficher le message
            $maSession->set('ajout', 1);
            
            #je declare une nouvelle instance
            $kit = new Produit;

            #je lie mon formulaire à la nouvelle instance
            $form = $this->createForm(KitType::class, $kit);
        }

        #je rénitialise mon slug
        $slug = 0;

        return $this->render('kit/ajouterKit.html.twig', [
            'licence' => 1,
            'slug' => $slug,
            'formProduit' => $form->createView(),
        ]);
    }
}
