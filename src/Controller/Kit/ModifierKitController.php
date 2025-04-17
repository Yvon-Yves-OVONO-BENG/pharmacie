<?php

namespace App\Controller\Kit;

use App\Form\KitType;
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
class ModifierKitController extends AbstractController
{
    public function __construct(
        protected EntityManagerInterface $em,
        protected TranslatorInterface $translator,
        protected ProduitRepository $produitRepository,
    )
    {}
    
    #[Route('/modifier-kit/{slug}', name: 'modifier_kit')]
    public function modifierKit(Request $request, string $slug): Response
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
        
        #je récupère la kit dont je veux modifier
        $kit = $this->produitRepository->findOneBySlug([
            'slug' => $slug
        ]);
        
        
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

        //je récupère la date de maintenant
        $now = new \DateTime('now');

        //////j'extrait la dernière kit de la table
        $derniereKit = $this->produitRepository->findBy([], ['id' => 'DESC'], 1, 0);

        if ($derniereKit) 
        {
            /////je récupère l'id du dernier abonnement
            $id = $derniereKit[0]->getId();
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
            
            $kit->setSlug($slug.$id)
            ->setPrixVente($prix);
            
            #je prepare ma requete
            $this->em->persist($kit);

            #j'exécute ma requête
            $this->em->flush();

            #j'affiche le message de confirmation
            $this->addFlash('info', $this->translator->trans('Kit modifiée avec succès !'));
            
            #j'affecte 1 à ma variable pour afficher le message
            $maSession->set('ajout', 1);
            
            #je redirige vers la liste des kits
            return $this->redirectToRoute('liste_kit', ['m' => 1 ]);
        }

        return $this->render('kit/ajouterKit.html.twig', [
            'slug' => $slug,
            'kit' => $kit,
            'licence' => 1,
            'formProduit' => $form->createView(),
        ]);
    }
}
