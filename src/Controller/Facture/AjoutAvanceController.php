<?php

namespace App\Controller\Facture;

use App\Entity\Facture;
use App\Entity\HistoriquePaiement;
use App\Entity\LigneDeFacture;
use App\Form\AjoutAvanceType;
use App\Repository\EtatFactureRepository;
use App\Repository\FactureRepository;
use App\Repository\LigneDeFactureRepository;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\HttpFoundation\Request;

/**
 * @IsGranted("ROLE_USER", message="Accès refusé. Espace reservé uniquement aux abonnés")
 *
 */
class AjoutAvanceController extends AbstractController
{
    public function __construct(
        protected EntityManagerInterface $em,
        protected FactureRepository $factureRepository,
        protected EtatFactureRepository $etatFactureRepository,
        protected LigneDeFactureRepository $ligneDeFactureRepository,
    )
    {}

    #[Route('/ajout-avance/{slug}', name: 'ajout_avance')]
    public function ajoutAvance(Request $request, $slug): Response
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

        #je récupère la facture dont je veux ajouter l'avance
        $facture = $this->factureRepository->findOneBySlug([
            'slug' => $slug
        ]);

        #je récupère le reste
        $reste = $facture->getNetAPayer() - $facture->getAvance();
            
        // 1. Nous voulons lire les données du formulaire
        //FormFactoryInterface / Request
        $newfacture = new Facture;

        $form = $this->createForm(AjoutAvanceType::class, $newfacture, ['reste' => $reste]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) 
        {
            $facture = $this->factureRepository->findOneBySlug([
                'slug' => $request->request->get('slugFacture')
            ]);

            $avanceActuelle = $facture->getAvance();
            $resteActuelle = $facture->getReste();
            $avance = $form->getData()->getAvance();
            
            $nouvelleAvance = $avanceActuelle + $avance;
            
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


            #je met à jour l'avance dans la tale facture
            $newfacture->setAvance($avance)
            ->setDateFactureAt(new DateTime('now'))
            ->setHeure(new DateTime('now'))
            ->setCaissiere($this->getUser())
            ->setReste($resteActuelle - $avance)
            ->setReference($reference)
            ->setNetAPayer($facture->getNetAPayer())
            ->setSlug(uniqid('', true))
            ->setAnnulee(0)
            ->setContactPatient($facture->getContactPatient())
            ->setPrescripteur($facture->getPrescripteur());

            if ($facture->getPatient()) 
            {
                $newfacture->setPatient($facture->getPatient());
            } 
            else 
            {
                $newfacture->setNomPatient($facture->getNomPatient());
            }

            // $ligneDeFactures = $facture->getLigneDeFactures();
            $ligneDeFactures = $this->ligneDeFactureRepository->findBy(['facture' => $facture]);
            
            foreach ($ligneDeFactures as $ligneDeFacture) 
            {
                #je déclare une ligne de facture pour chaque produit de la facture
                $newLigneDeFacture = new LigneDeFacture;

                #pour chaque ligne de facture
                $newLigneDeFacture->setFacture($newfacture)
                                ->setProduit($ligneDeFacture->getProduit())
                                ->setQuantite($ligneDeFacture->getQuantite())
                                ->setPrix($ligneDeFacture->getPrix())
                                ->setPrixQuantite($ligneDeFacture->getPrixQuantite())
                                ;

                $this->em->persist($newLigneDeFacture);
            }

            ######j'insère une nouvelle ligne dans la table historique paiement
            $historiquePaiement = new HistoriquePaiement;

            $historiquePaiement->setFacture($facture)
            ->setMontantAvance($avance)
            ->setDateAvanceAt(new DateTime('now'))
            ->setRecuPar($this->getUser());

            ####SI LE NET A PAYER EST EGAL A LA NOUVELLE AVANCE, ON MET LA FACTURE A SOLDE
            if (($newfacture->getReste()) == 0) 
            {
                $newfacture->setEtatFacture($this->etatFactureRepository->find(1));
            }
            else
            {
                $newfacture->setEtatFacture($this->etatFactureRepository->find(2));
            }

            #je persiste mes entités
            $this->em->persist($newfacture);
            $this->em->persist($historiquePaiement);

            #je demande à entity manager d'exécuter la requête
            $this->em->flush();

            $this->addFlash('info', 'Avance ajoutée avec succès !');

            return $this->redirectToRoute('liste_facture', ['m' => 1]);

        }

        return $this->render('facture/ajoutAvance.html.twig', [
            'licence' => 1,
            'facture' => $facture,
            'ajoutAvanceForm' => $form->createView(),
        ]);
    }
}
