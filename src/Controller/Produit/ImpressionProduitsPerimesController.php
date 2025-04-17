<?php

namespace App\Controller\Produit;

use App\Repository\ProduitRepository;
use App\Service\ImpressionProduitsPerimesService;
use DateTime;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * @IsGranted("ROLE_USER", message="Accès refusé. Espace reservé uniquement aux abonnés")
 *
 */
class ImpressionProduitsPerimesController extends AbstractController
{
    public function __construct(
        protected ProduitRepository $produitRepository,
        protected ImpressionProduitsPerimesService $impressionProduitsPerimesService
    )
    {}

    #[Route('/produits-perimes/{bientot}', name: 'produits_perimes')]
    public function produitsPerimes(Request $request, int $bientot = 0): Response
    {
        # je récupère ma session
        $maSession = $request->getSession();

        if(!$maSession)
        {
            return $this->redirectToRoute("app_logout");
        }

        # la date du jour avec une heure 00:00:00
        $aujourdhui = date_create(date_format(new DateTime('now'), 'Y-m-d'), timezone_open('Pacific/Nauru'));

        // ->diff(date(produit.lot.datePeremptionAt|date())).format('%R%a');
        
        #tableau des produits perimes
        $produitsPerimes = [];

        #je récupère les produits
        $produits = $this->produitRepository->produits();
        
        if ($bientot) 
        {
            foreach ($produits as $produit) 
            {
                #je récupère le nombre de jour entre la date du jour et la date de peremption du produit
                // $dateDiff = date_diff($aujourdhui, $produit->getLot()->getDatePeremptionAt())->format('%R%a');
                #date du jour
                // $aujourdhui = date_create(date_format(new DateTime('now'), 'Y-m-d'), timezone_open('Pacific/Nauru'));
                $aujourdhui = date_format(new DateTime('now'), 'Y-m-d');
                $aujourdhui = new DateTime($aujourdhui);

                $datePeremption = date_format($produit->getLot()->getDatePeremptionAt(), ('Y-m-d'));
                $datePeremption = new DateTime($datePeremption);

                $dateDiff = $aujourdhui->diff($datePeremption);
                
                if ((int)$dateDiff->format('%R%a') <= 90 && ((int)$dateDiff->format('%R%a') > 0) && ($produit->isSupprime() == 0)) 
                {
                    $produitsPerimes[] = $produit;
                }
            
            }
        } 
        else 
        {
            foreach ($produits as $produit) 
            {
                #je récupère le nombre de jour entre la date du jour et la date de peremption du produit
                $aujourdhui = date_format(new DateTime('now'), 'Y-m-d');
                $aujourdhui = new DateTime($aujourdhui);

                $datePeremption = date_format($produit->getLot()->getDatePeremptionAt(), ('Y-m-d'));
                $datePeremption = new DateTime($datePeremption);

                $dateDiff = $aujourdhui->diff($datePeremption);
                
                if ((int)$dateDiff->format('%R%a') <= 0 && $produit->isSupprime() == 0) 
                {
                    $produitsPerimes[] = $produit;
                }
            
            }
        }
        
       
        $pdf = $this->impressionProduitsPerimesService->impressionProduitsPerimes($produitsPerimes, $bientot);
        return new Response($pdf->Output(utf8_decode("Produits périmés"), "I"), 200, ['content-type' => 'application/pdf']);
    }
}
