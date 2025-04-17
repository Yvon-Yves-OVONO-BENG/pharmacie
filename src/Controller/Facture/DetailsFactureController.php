<?php

namespace App\Controller\Facture;

use App\Repository\FactureRepository;
use App\Repository\LigneDeFactureRepository;
use Doctrine\ORM\EntityManagerInterface;
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
class DetailsFactureController extends AbstractController
{
    public function __construct(
        protected EntityManagerInterface $em,
        protected FactureRepository $factureRepository, 
        protected LigneDeFactureRepository $ligneDeFactureRepository, 
        )
    {}

    #[Route('/details-facture/{slug}/{m<[0-1]{1}>}', name: 'details_facture')]
    public function detailsFacture(Request $request, $slug, $m = 0): Response
    {
        # je récupère ma session
        $maSession = $request->getSession();

        if(!$maSession)
        {
            return $this->redirectToRoute("app_logout");
        }

        #je teste si le témoin n'est pas vide pour savoir s'il vient de la mise à jour
        if ($m == 1) 
        {
            #mes variables témoin pour afficher les sweetAlert
            $maSession->set('ajout', 1);
            $maSession->set('suppression', null);
            
        }

        $facture = $this->factureRepository->findOneBySlug([
            'slug' => $slug
        ]);

        $this->em->flush();

        $ligneDeFactures = $this->ligneDeFactureRepository->findBy([
            'facture' => $facture->getId()
        ]);

        return $this->render('facture/detailsFacture.html.twig', [
            'licence' => 1,
            'facture' => $facture,
            'ligneDeFactures' => $ligneDeFactures
        ]);
    }
}
