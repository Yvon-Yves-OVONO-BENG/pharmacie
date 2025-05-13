<?php

namespace App\Controller\Examen;

use App\Entity\ConstantsClass;
use App\Entity\Produit;
use App\Form\ExamenType;
use App\Service\StrService;
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
#[Route('/examen')]
class AjouterExamenController extends AbstractController
{
    public function __construct(
        protected StrService $strService,
        protected EntityManagerInterface $em,
        protected TranslatorInterface $translator,
        protected ProduitRepository $produitRepository
    )
    {}

    #[Route('/ajouter-examen', name: 'ajouter_examen')]
    public function ajouterExamen(Request $request): Response
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
        
        

        #j'initialise le slug
        $slug = 0;

        #je céclare une nouvelle instance examen
        $examen = new Produit;

        #je lie mon formulaire à ma nouvelle instance
        $form = $this->createForm(ExamenType::class, $examen);

        #je demande à mon formulaire de gueter tout ce qui est dans le POST
        $form->handleRequest($request);

        #je construis le code pour la reference de la examen
        $characts    = 'abcdefghijklmnopqrstuvwxyz';
        $characts   .= 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $characts   .= '1234567890';
        $slug      = '';

        for ($i = 0; $i < 11; $i++) {
            $slug .= substr($characts, rand() % (strlen($characts)), 1);
        }

        //////j'extrait la dernière examen de la table
        $derniereProduit = $this->produitRepository->findBy([], ['id' => 'DESC'], 1, 0);

        if ($derniereProduit) 
        {
            /////je récupère l'id du dernier abonnement
            $id = $derniereProduit[0]->getId();
        } 
        else 
        {
            $id = 1;
        }

        if ($form->isSubmitted() && $form->isValid()) 
        {
            $examen->setLibelle($this->strService->strToUpper($examen->getLibelle()))
            ->setExamen(1)
            ->setSupprime(0)
            ->setSlug($slug.$id)
            ->setPhoto(ConstantsClass::NOM_PRODUIT)
            ;

            
            #je prepare ma requete
            $this->em->persist($examen);

            #j'exécute ma requête
            $this->em->flush();

            #j'affiche le message de confirmation
            $this->addFlash('info', $this->translator->trans('Examen ajoutée avec succès !'));
            
            #j'affecte 1 à ma variable pour afficher le message
            $maSession->set('ajout', 1);
            
            

            #je declare une nouvelle instance
            $examen = new Produit;

            #je lie mon formulaire à la nouvelle instance
            $form = $this->createForm(ExamenType::class, $examen);
        }

        #je rénitialise mon slug
        $slug = 0;

        return $this->render('examen/ajouterExamen.html.twig', [
            'licence' => 1,
            'slug' => $slug,
            'formProduit' => $form->createView(),
        ]);
    }
}
