<?php

namespace App\Controller\Facture;

use App\Entity\ConstantsClass;
use App\Entity\HistoriquePaiement;
use App\Repository\FactureRepository;
use Doctrine\ORM\EntityManagerInterface;
use App\Repository\EtatFactureRepository;
use DateTime;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;

/**
 * @IsGranted("ROLE_USER", message="Accès refusé. Espace reservé uniquement aux abonnés")
 *
 */
#[Route('/facture')]
class ValiderFactureController extends AbstractController
{
    public function __construct(protected FactureRepository $factureRepository, protected EtatFactureRepository $etatFactureRepository, protected EntityManagerInterface $em)
    {}

    #[Route('/valider-facture/{slug}', name: 'valider_facture')]
    public function validerFacture(Request $request, $slug): Response
    {
        # je récupère ma session
        $maSession = $request->getSession();

        if(!$maSession)
        {
            return $this->redirectToRoute("app_logout");
        }
        
        $facture = $this->factureRepository->findOneBySlug([
            'slug' => $slug
        ]);
     
        $etatFacture = $this->etatFactureRepository->findOneByEtatFacture([
            'etatFacture' => ConstantsClass::SOLDE
        ]);

        $user = $this->getuser();

        #JE CREE UNE LIGNE DE HISTORIQUE PAIEMENT
        $historiquePaiement = new HistoriquePaiement;

        $historiquePaiement->setFacture($facture)
        ->setMontantAvance($facture->getNetApayer() - $facture->getAvance())
        ->setRecuPar($this->getUser())
        ->setDateAvanceAt(new DateTime('now'));

        ###
        $facture->setEtatFacture($etatFacture)
                ->setCaissiere($user)
                ->setAvance($facture->getNetApayer())
                ->setDateFactureAt(new DateTime('now'))
                ;
        
        $this->em->persist($historiquePaiement);
        $this->em->persist($facture);
        $this->em->flush();

        $this->addFlash('info', 'Facture Validée avec succès !');

        #j'affecte 1 à ma variable pour afficher le message
        $maSession->set('ajout', 1);
                
        

        return  $this->redirectToRoute('liste_facture', [ 'm' => 1 ]);
    }
}
