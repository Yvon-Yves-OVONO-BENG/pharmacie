<?php

namespace App\Controller\Commande;

use App\Repository\CommandeRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

/**
 * @IsGranted("ROLE_USER", message="Accès refusé. Espace reservé uniquement aux abonnés")
 *
 */
#[Route('/commande')]
class DetailsCommandeController extends AbstractController
{
    public function __construct(
        protected CommandeRepository $commandeRepository,
    )
    {}

    #[Route('/details-commande/{slug}', name: 'details_commande')]
    public function detailsCommande(Request $request, string $slug): Response
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
        
        

        #je récupère la commande dont je veux modifier
        $commande = $this->commandeRepository->findOneBySlug([
            'slug' => $slug
        ]);

        #Produits périmés dans 3 mois
        $produitsPerimesDansTroisMois = 0;

        // foreach ($produits as $produit) 
        // {
        //     $aujourdhui = date_format(new DateTime('now'), 'Y-m-d');
        //     $aujourdhui = new DateTime($aujourdhui);

        //     $datePeremption = date_format($produit->getDatePeremptionAt(), ('Y-m-d'));
        //     $datePeremption = new DateTime($datePeremption);

        //     $dateDiff = $aujourdhui->diff($datePeremption);

        //     if ((int)$dateDiff->format('%R%a') <= 90 && ((int)$dateDiff->format('%R%a') > 0) && ($produit->isSupprime() == 0)) 
        //     {
        //         $produitsPerimesDansTroisMois = $produitsPerimesDansTroisMois + 1;
        //     }
        // }


        #Produits périmés
        $produitsPerimes = 0;

        // foreach ($produits as $produit) 
        // {
        //     $aujourdhui = date_format(new DateTime('now'), 'Y-m-d');
        //     $aujourdhui = new DateTime($aujourdhui);

        //     $datePeremption = date_format($produit->getDatePeremptionAt(), ('Y-m-d'));
        //     $datePeremption = new DateTime($datePeremption);

        //     $dateDiff = $aujourdhui->diff($datePeremption);

        //     if ((int)$dateDiff->format('%R%a') <= 0) 
        //     {
        //         $produitsPerimes = $produitsPerimes + 1;
        //     }
        // }
        
        #je récupère le netApayer de la commande
        $netApayer = 0 ;
        foreach ($commande->getLigneDecommandes() as $ligneDeCommande) 
        {
            $netApayer += $ligneDeCommande->getPrixAchat() * $ligneDeCommande->getQuantite();
        }

        return $this->render('commande/detailsCommande.html.twig', [
            'slug' => $slug,
            'licence' => 1,
            'netApayer' => $netApayer,
            'commande' => $commande,
        ]);
    }
}
