<?php

namespace App\Controller\Facture;

use App\Entity\Facture;
use App\Entity\ConstantsClass;
use App\Entity\HistoriquePaiement;
use App\Entity\LigneDeFacture;
use App\Service\PanierService;
use App\Form\ConfirmerPanierType;
use App\Repository\FactureRepository;
use App\Repository\ProduitRepository;
use Doctrine\ORM\EntityManagerInterface;
use App\Repository\EtatFactureRepository;
use App\Repository\LigneDeFactureRepository;
use App\Service\ImpressionFactureService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

/**
 * @IsGranted("ROLE_USER", message="Accès refusé. Espace reservé uniquement aux abonnés")
 *
 */
#[Route('/facture')]
class ConfirmerFactureController extends AbstractController
{
    public function __construct( 
        protected EntityManagerInterface $em, 
        protected PanierService $panierService, 
        protected TranslatorInterface $translator,
        protected EtatFactureRepository $etatFactureRepository, 
        protected ProduitRepository $produitRepository,  
        protected FactureRepository $factureRepository,
        protected LigneDeFactureRepository $ligneDeFactureRepository,
        protected ImpressionFactureService $impressionFactureService,
        )
    {}

    #[Route('/confirmer-facture/{slug}', name: 'confirmer_facture')]
    
    public function confirmerFacture(Request $request, string $slug = ""): Response
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
        
        if ($slug) 
        {
            #je récupère la facture dont je veux valider le solde
            $facture = $this->factureRepository->findOneBySlug([
                'slug' => $slug
            ]); 

            $form = $this->createForm(ConfirmerPanierType::class, $facture);
            $form->handleRequest($request);

            if ($form->isSubmitted() && $form->isValid()) 
            {
                /**
                 * @var Facture
                 */
                $facture = $form->getData();
                
                #je set l'état de facture en fonction du mode de paiement
                switch ($facture->getModePaiement()->getModePaiement()) 
                {
                    case ConstantsClass::CASH:
                        ///je récupère l'état EN COURS pour setter la cammande qui vient d'être passé
                        $solde = $this->etatFactureRepository->findOneByEtatFacture([
                            'etatFacture' => ConstantsClass::SOLDE
                        ]);

                        $facture->setEtatFacture($solde);
                        break;

                    case ConstantsClass::PRIS_EN_CHARGE:
                        ///je récupère l'état EN COURS pour setter la cammande qui vient d'être passé
                        $prisEnCharge = $this->etatFactureRepository->findOneByEtatFacture([
                            'etatFacture' => ConstantsClass::NON_SOLDE
                        ]);
                        $facture->setEtatFacture($prisEnCharge);
                        break;

                    case ConstantsClass::CREDIT:
                        ///je récupère l'état EN COURS pour setter la cammande qui vient d'être passé
                        $nonSolde = $this->etatFactureRepository->findOneByEtatFacture([
                            'etatFacture' => ConstantsClass::NON_SOLDE
                        ]);
                        $facture->setEtatFacture($nonSolde);
                        break;
                    
                }

                // 4. Nous allons la lier avec l'utilisateur actuellement connecté (Security)
                
                #je fabrique mon slug
                $characts    = 'abcdefghijklmnopqrstuvwxyz#{};()';
                $characts   .= 'ABCDEFGHIJKLMNOPQRSTUVWXYZ#{};()';	
                $characts   .= '1234567890'; 
                $slug      = ''; 
        
                for($i=0;$i < 11;$i++) 
                { 
                    $slug .= substr($characts,rand()%(strlen($characts)),1); 
                }

                //je récupère la date de maintenant
                $now = new \DateTime('now');

                //////j'extrait la dernière facture de la table
                $derniereFacture = $this->factureRepository->findBy([],['id' => 'DESC'],1,0);

                if(!$derniereFacture)
                {
                    $id = 1;
                }
                else
                {
                    /////je récupère l'id de la dernière facture
                    $id = $derniereFacture[0]->getId();

                }

                if ($facture->getPatient()) 
                {
                    $facture->setPatient($facture->getPatient());
                } 
                else 
                {
                    $facture->setNomPatient(ConstantsClass::NOM_PATIENT)
                        ->setContactPatient(ConstantsClass::CONTACT_PATIENT)
                    ;
                }
                
                $facture->setDateFactureAt($now)
                        ->setHeure($now)
                        ->setSlug($slug.$id)
                        ->setAnnulee(0)
                        ;
                
                $this->em->persist($facture);

                // 6. Nous allons enregistrer la facture (EntityManagerInterface)
                $this->em->flush(); 

                #j'affiche le message de confirmation d'ajout
                $this->addFlash('info', $this->translator->trans('Facture enregistrée avec succès !'));

                #j'affecte 1 à ma variable pour afficher le message
                $maSession->set('ajout', 1);

                return  $this->redirectToRoute('liste_facture', [ 'm' => 1 ]);
                
            }

        } 
        else 
        {
            // 1. Nous voulons lire les données du formulaire
            //FormFactoryInterface / Request
            $form = $this->createForm(ConfirmerPanierType::class);

            $form->handleRequest($request);

            ////je récupère l'utilisateur connecté
            $user = $this->getUser();

            // 2. Si il y'a pas de produits dans mon panier : dégager(PanierService)
            $produits = $this->panierService->getDetailsPanierProduits();

            if (count($produits) === 0) 
            {
                //$this->addFlash('info', 'Vous ne pouvez pas confirmer une facture avec un panier vide');
                return $this->redirectToRoute('panier_afficher');
            }
            
            
            if ($form->isSubmitted() && $form->isValid()) 
            {
                // 3. Nous allons créer une facture
                /**
                 * @var Facture
                 */
                $facture = $form->getData();
                
                #je set l'état de facture en fonction du mode de paiement
                switch ($facture->getModePaiement()->getModePaiement()) 
                {
                    case ConstantsClass::CASH:
                        ///je récupère l'état EN COURS pour setter la cammande qui vient d'être passé
                        $solde = $this->etatFactureRepository->findOneByEtatFacture([
                            'etatFacture' => ConstantsClass::SOLDE
                        ]);

                        $nonSolde = $this->etatFactureRepository->findOneByEtatFacture([
                            'etatFacture' => ConstantsClass::NON_SOLDE
                        ]);

                        if ($facture->getAvance() == $facture->getNetAPayer()) 
                        {
                            $facture->setEtatFacture($solde);
                        }
                        else
                        {
                            $facture->setEtatFacture($nonSolde);
                        }
                        
                        $facture->setNetAPayer($this->panierService->getTotal());
                        break;

                    case ConstantsClass::PRIS_EN_CHARGE:
                        ///je récupère l'état EN COURS pour setter la cammande qui vient d'être passé
                        $nonSolde = $this->etatFactureRepository->findOneByEtatFacture([
                            'etatFacture' => ConstantsClass::NON_SOLDE
                        ]);
                        
                        $facture->setEtatFacture($nonSolde)
                        ->setNetAPayer($this->panierService->getTotal()*2)
                        ;
                        break;

                    case ConstantsClass::CREDIT:
                        ///je récupère l'état EN COURS pour setter la cammande qui vient d'être passé
                        $nonSolde = $this->etatFactureRepository->findOneByEtatFacture([
                            'etatFacture' => ConstantsClass::NON_SOLDE
                        ]);
                        
                        $facture->setEtatFacture($nonSolde)
                        ->setNetAPayer($this->panierService->getTotal());
                        break;
                    
                }

                // 4. Nous allons la lier avec l'utilisateur actuellement connecté (Security)
                
                #je fabrique mon slug
                $characts    = 'abcdefghijklmnopqrstuvwxyz#{};()';
                $characts   .= 'ABCDEFGHIJKLMNOPQRSTUVWXYZ#{};()';	
                $characts   .= '1234567890'; 
                $slug      = ''; 
        
                for($i=0;$i < 11;$i++) 
                { 
                    $slug .= substr($characts,rand()%(strlen($characts)),1); 
                }

                //je récupère la date de maintenant
                $now = new \DateTime('now');

                ///j'extrais le jour de la date du jour en numérique
                $jour = $now->format('d');

                ///j'exrais le mois de la date du jour en numérique
                $mois = $now->format('m');

                ///j'extrais l'annéé de la dat du jour en numérique
                $annee = $now->format('Y');

                $annee = substr($annee, 2, 2);

                //////j'extrait la dernière facture de la table
                $derniereFacture = $this->factureRepository->findBy([],['id' => 'DESC'],1,0);

                if(!$derniereFacture)
                {
                    $id = 1;
                }
                else
                {
                    /////je récupère l'id de la dernière facture
                    $id = $derniereFacture[0]->getId();

                }

                /////je construis la référence
                $reference = 'PP-'.$id.$jour.$mois.$annee;

                if ($facture->getPatient()) 
                {
                    $facture->setPatient($facture->getPatient());
                } 
                // else 
                // {
                //     $facture->setNomPatient(ConstantsClass::NOM_PATIENT)
                //         ->setContactPatient(ConstantsClass::CONTACT_PATIENT)
                //     ;
                // }

                $facture->setCaissiere($user)
                        ->setDateFactureAt($now)
                        ->setHeure($now)
                        ->setReference($reference)
                        ->setSlug($slug.$id)
                        ->setAnnulee(0)
                        ;
                
                $this->em->persist($facture);

                /////NOUVELLE HISTORIQUE FACTURE
                $historiquePaiement = new HistoriquePaiement;

                $historiquePaiement->setFacture($facture)
                ->setDateAvanceAt($now)
                ->setMontantAvance($facture->getAvance())
                ->setRecuPar($this->getUser());
                
                $this->em->persist($historiquePaiement);
                
                // 5. Nous allons avec les produits qui sont dans le panier (PanierService)
                foreach ($this->panierService->getDetailsPanierProduits() as $panierProduit) 
                {
                    #je déclare une ligne de facture pour chaque produit de la facture
                    $ligneDeFacture = new LigneDeFacture;
                    
                    #pour chaque ligne de facture
                    $ligneDeFacture->setFacture($facture)
                                    ->setProduit($panierProduit->produit)
                                    ->setPrix($panierProduit->produit->getPrixVente())
                                    ->setQuantite($panierProduit->qte);
                    
                    switch ($facture->getModePaiement()->getModePaiement()) 
                    {
                        case ConstantsClass::CASH:
                            $ligneDeFacture->setPrixQuantite($panierProduit->getTotal());
                            break;
    
                        case ConstantsClass::PRIS_EN_CHARGE:
                            $ligneDeFacture->setPrixQuantite($panierProduit->getTotal()*2);
                            break;
    
                        case ConstantsClass::CREDIT:
                            $ligneDeFacture->setPrixQuantite($panierProduit->getTotal());
                            break;
                        
                    }

                    if ($panierProduit->produit->isKit()) 
                    {   
                        foreach ($panierProduit->produit->getProduitLigneDeKits() as $ligneDeKit) 
                        {   
                            #quantite de la facture
                            $quantiteFacture = $ligneDeKit->getQuantite() * $panierProduit->qte;
                            
                            // dd($lot);
                            if ($ligneDeKit->getProduit()->getLot()) 
                            {
                                #nombreVendu dans un lot
                                $ancienneQuantiteVenduLot = $ligneDeKit->getProduit()->getLot()->getVendu();

                                #nouvelle quantité vendu
                                $nouvelleQuaniteVenduLot = $ancienneQuantiteVenduLot + $quantiteFacture;
                                
                                $ligneDeKit->getProduit()->getLot()->setVendu($nouvelleQuaniteVenduLot);

                                $this->em->persist($ligneDeKit->getProduit()->getLot());

                            }

                            $this->em->persist($ligneDeFacture);
                            $this->em->persist($ligneDeKit);
                            
                        }
                    } 
                    else 
                    {
                        #je récupère chaque produit pour diminuer le nombre en stock
                        $produi = $this->produitRepository->findOneBySlug([
                            'slug' => $panierProduit->produit->getSlug()
                        ]);

                        #quantite de la facture
                        $quantiteFacture = $panierProduit->qte;

                        #je récupère le lot
                        $lot = $produi->getLot();

                        if ($lot) 
                        {
                            #nombreVendu dans un lot
                            $ancienneQuantiteVenduLot = $lot->getVendu();

                            #nouvelle quantité vendu
                            $nouvelleQuaniteVenduLot = $ancienneQuantiteVenduLot + $quantiteFacture;
                            
                            $lot->setVendu($nouvelleQuaniteVenduLot);
                            $this->em->persist($lot);

                        }

                        $this->em->persist($ligneDeFacture);
                        $this->em->persist($produi);
                    }
                    
                }

                // 6. Nous allons enregistrer la facture (EntityManagerInterface)
                $this->em->flush(); 
                
                #je vide le panier
                $this->panierService->viderPanier();

                #j'affiche le message de confirmation d'ajout
                $this->addFlash('info', $this->translator->trans('Facture payée avec succès !'));

                #j'affecte 1 à ma variable pour afficher le message
                $maSession->set('ajout', 1);

                return  $this->redirectToRoute('details_facture', [ 'slug' => $slug.$id, 'm' => 1 ]);
                
            }
        }
        
        return $this->render('facture/confirmerFacture.html.twig', [
            'licence' => 1,
            'confirmerFactureForm' => $form->createView(),
        ]);
    }
}
