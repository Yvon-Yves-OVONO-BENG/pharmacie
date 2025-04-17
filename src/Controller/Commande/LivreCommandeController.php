<?php

namespace App\Controller\Commande;

use App\Repository\CommandeRepository;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

/**
 * @IsGranted("ROLE_USER", message="Accès refusé. Espace reservé uniquement aux abonnés")
 *
 */
#[Route('/commande')]
class LivreCommandeController extends AbstractController
{
    public function __construct(
        protected EntityManagerInterface $em,
        protected TranslatorInterface $translator,
        protected CommandeRepository $commandeRepository,
    )
    {}

    #[Route('/livre-commande/{slug}', name: 'livre_commande')]
    public function livreCommande(Request $request, String $slug): Response
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
        
        # je récupère la commande dont je veux modifier l'état
        $commande = $this->commandeRepository->findOneBySlug([
            'slug' => $slug
        ]);

        #je set la livraison et la date du jour
        $commande
        ->setLivre(1)
        ->setDateLivraisonAt(new DateTime('now'));

        #je prépare ma requete
        $this->em->persist($commande);

        #j'exécute ma requete
        $this->em->flush();

        #j'affiche le message de confirmation
        $this->addFlash('info', $this->translator->trans('Commande livrée avec succès !'));
            
        #j'affecte 1 à ma variable pour afficher le message
        $maSession->set('ajout', 1);
        
        

        #je redirige vers la liste des commandes
        return $this->redirectToRoute('liste_commande', ['m' => 1 ]);
    }
}
