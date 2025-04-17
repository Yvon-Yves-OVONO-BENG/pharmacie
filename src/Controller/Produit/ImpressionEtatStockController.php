<?php

namespace App\Controller\Produit;

use DateTime;
use App\Repository\FactureRepository;
use App\Repository\LigneDeFactureRepository;
use App\Repository\ProduitRepository;
use App\Service\ImpressionEtatStockService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

/**
 * @IsGranted("ROLE_USER", message="Accès refusé. Espace reservé uniquement aux abonnés")
 *
 */
class ImpressionEtatStockController extends AbstractController
{
    public function __construct(
        protected FactureRepository $factureRepository,
        protected ProduitRepository $produitRepository,
        protected LigneDeFactureRepository $ligneDeFactureRepository,
        protected ImpressionEtatStockService $impressionEtatStockService
    )
    {}

    #[Route('/impression-etat-stock/{periode}', name: 'impression_etat_stock')]
    public function impressionProduit(Request $request, int $periode = 0): Response
    {
        # je récupère ma session
        $maSession = $request->getSession();

        if(!$maSession)
        {
            return $this->redirectToRoute("app_logout");
        }
        
        if ($periode) 
        {
            if ($request->request->has('impressionEtatStock')) 
            {
                // $dateDebut = DateTime::createFromFormat('Y-m-d',$request->request->get('dateDebut'));
                $dateDebut = date_create($request->request->get('dateDebut'));
                $dateFin = date_create($request->request->get('dateFin'));
                
                #je récupères les lignes de factures de la base de données
                // $ligneDeFactures = $this->ligneDeFactureRepository->facturesVenduesPeriode($dateDebut, $dateFin);
                $ligneDeFactures = $this->ligneDeFactureRepository->etatStockPeriode($dateDebut, $dateFin);
                
                $produits = [];
                foreach ($ligneDeFactures as $ligneDeFacture) 
                {
                    if ($ligneDeFacture->getProduit()->isKit() == 0) 
                    {
                        $produits[] = $ligneDeFacture->getProduit();
                    }
                    
                }
                
                $pdf = $this->impressionEtatStockService->impressionEtatStock($produits, $dateDebut, $dateFin, $periode);
            }
        } 
        else 
        {
            $periode = 0;
            #je récupères les produits de la base de données
            $produits = $this->produitRepository->findBy([
                'kit' => 0,
                'supprime' => 0,
            ], ['libelle' => 'ASC']);
            
            $pdf = $this->impressionEtatStockService->impressionEtatStock($produits);
        }
        
        return new Response($pdf->Output(utf8_decode("Etat du stock"), "I"), 200, ['content-type' => 'application/pdf']);
    
    }
}
