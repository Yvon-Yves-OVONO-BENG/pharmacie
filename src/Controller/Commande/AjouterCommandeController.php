<?php

namespace App\Controller\Commande;

use App\Entity\Commande;
use App\Form\CommandeType;
use App\Repository\CommandeRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @IsGranted("ROLE_USER", message="Accès refusé. Espace reservé uniquement aux abonnés")
 *
 */
#[Route('/commande')]
class AjouterCommandeController extends AbstractController
{
    public function __construct(
        protected EntityManagerInterface $em,
        protected TranslatorInterface $translator,
        protected CommandeRepository $commandeRepository
    )
    {}

    #[Route('/ajouter-commande', name: 'ajouter_commande')]
    public function ajouterCommande(Request $request): Response
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

        #je céclare une nouvelle instance commande
        $commande = new Commande;

        #je lie mon formulaire à ma nouvelle instance
        $form = $this->createForm(CommandeType::class, $commande);

        #je demande à mon formulaire de gueter tout ce qui est dans le POST
        $form->handleRequest($request);

        #je construis le code pour la reference de la commande
        $characts    = 'abcdefghijklmnopqrstuvwxyz';
        $characts   .= 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $characts   .= '1234567890';
        $slug      = '';

        for ($i = 0; $i < 5; $i++) {
            $slug .= substr($characts, rand() % (strlen($characts)), 1);
        }

        //je récupère la date de maintenant
        $now = new \DateTime('now');

        ///j'extrais le jour de la date du jour en numérique
        $jour = $now->format('d');

        ///j'extrais le mois de la date du jour en numérique
        $mois = $now->format('m');

        ///j'extrais l'annéé de la dat du jour en numérique
        $annee = $now->format('Y');

        //////j'extrait la dernière commande de la table
        $derniereCommande = $this->commandeRepository->findBy([], ['id' => 'DESC'], 1, 0);

        if ($derniereCommande) 
        {
            /////je récupère l'id du dernier abonnement
            $id = $derniereCommande[0]->getId();
        } 
        else 
        {
            $id = 1;
        }

        /////je construis la référence
        $reference = 'COM-'.$id.$jour.$mois.$annee ;

        if ($form->isSubmitted() && $form->isValid()) 
        {

            $commande->setReference($reference)
                ->setDateEntreeAt(new \DateTime('now'))
                ->setSecretaire($this->getUser())
                ->setSlug($slug.$id)
            ;

            foreach ($commande->getLigneDeCommandes() as $ligneDeCommande) 
            {
                $ligneDeCommande->setPrixVente($ligneDeCommande->getPrixAchat()*$ligneDeCommande->getCoef());

                $this->em->persist($ligneDeCommande);
            }
            
            #je prepare ma requete
            $this->em->persist($commande);

            #j'exécute ma requête
            $this->em->flush();

            #j'affiche le message de confirmation
            $this->addFlash('info', $this->translator->trans('Commande ajoutée avec succès !'));
            
            #j'affecte 1 à ma variable pour afficher le message
            $maSession->set('ajout', 1);
            
            

            #je declare une nouvelle instance
            $commande = new Commande;

            #je lie mon formulaire à la nouvelle instance
            $form = $this->createForm(CommandeType::class, $commande);
        }

        #je rénitialise mon slug
        $slug = 0;

        return $this->render('commande/ajouterCommande.html.twig', [
            'slug' => $slug,
            'licence' => 1,
            'commandeForm' => $form->createView(),
        ]);
    }
}
