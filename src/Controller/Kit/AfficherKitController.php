<?php

namespace App\Controller\Kit;

use App\Form\KitType;
use App\Repository\ProduitRepository;
use App\Repository\LigneDeKitRepository;
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
class AfficherKitController extends AbstractController
{
    public function __construct(
        protected EntityManagerInterface $em,
        protected TranslatorInterface $translator,
        protected ProduitRepository $produitRepository,
        protected LigneDeKitRepository $ligneDeKitsRepository,
    )
    {}
    
    #[Route('/afficher-kit/{slug}/{s}', name: 'afficher_kit')]
    public function affichererKit(Request $request, string $slug, int $s = 0): Response
    {
        # je récupère ma session
        $maSession = $request->getSession();

        if(!$maSession)
        {
            return $this->redirectToRoute("app_logout");
        }

        #je teste si le témoin n'est pas vide pour savoir s'il vient de la suppression
        if ($s == 1) 
        {
            #mes variables témoin pour afficher les sweetAlert
            $maSession->set('ajout', null);
            $maSession->set('suppression', 1);
            
        }
        

        #je récupère la kit dont je veux modifier
        $kit = $this->produitRepository->findOneBySlug([
            'slug' => $slug
        ]);

        #je récupère tous les kits
        $kits = $this->produitRepository->findBy([
            'kit' => 1,
            'supprime' => 0,
        ]);

        #je calcul le prix du kit
        $prix = 0;
        $ligneDeKits = $kit->getProduitLigneDeKits();

        foreach ($ligneDeKits as $ligneDeKit) 
        {
            $prix += ($ligneDeKit->getProduit()->getPrixVente() * $ligneDeKit->getQuantite());

        }



        return $this->render('kit/modifierKit.html.twig', [
            'slug' => $slug,
            'kit' => $kit,
            'kits' => $kits,
            'licence' => 1,
        ]);
    }
}
