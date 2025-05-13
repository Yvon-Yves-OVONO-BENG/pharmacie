<?php

namespace App\Repository;

use DateTime;
use App\Entity\Lot;
use App\Entity\User;
use App\Entity\Facture;
use App\Entity\Produit;
use App\Entity\EtatFacture;
use App\Entity\TypeProduit;
use App\Entity\ModePaiement;
use App\Entity\ConstantsClass;
use App\Entity\HistoriquePaiement;
use App\Entity\LigneDeFacture;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Validator\Constraints\Date;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;

/**
 * @extends ServiceEntityRepository<Facture>
 *
 * @method Facture|null find($id, $lockMode = null, $lockVersion = null)
 * @method Facture|null findOneBy(array $criteria, array $orderBy = null)
 * @method Facture[]    findAll()
 * @method Facture[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class FactureRepository extends ServiceEntityRepository
{
    public function __construct(
        ManagerRegistry $registry,
        protected EntityManagerInterface $em)
    {
        parent::__construct($registry, Facture::class);
    }

    /**
     * function qui retourne les factures d'une période
     *
     * @param User $caissiere
     * @param DateTime $dateDebut
     * @param DateTime $dateFin
     * @return array
     */
    public function facturePeriode(User $caissiere = null, EtatFacture $etatFacture = null, DateTime $dateDebut = null, DateTime $dateFin = null): array
    {
        $qb = $this->createQueryBuilder('f')
            ->andWhere('f.annulee = 0')
            ;
            if($caissiere)
            {
                $qb->andWhere('f.caissiere = :caissiere')
                ->setParameter('caissiere', $caissiere);
            }
            if($dateDebut && $dateFin)
            {
                $qb->andWhere('f.dateFactureAt BETWEEN :dateDebut AND :dateFin')
                ->setParameter('dateDebut', $dateDebut)
                ->setParameter('dateFin', $dateFin);
            }

            if($etatFacture)
            {
                $qb->andWhere('f.etatFacture = :etatFacture')
                ->setParameter('etatFacture', $etatFacture);
            }

        return $qb->getQuery()->getResult();
    }


    public function facturePeriodeDonnee(DateTime $dateDebut, DateTime $dateFin): array
    {
        $qb = $this->createQueryBuilder('f')
            ->andWhere('f.annulee = 0')
            ->andWhere('f.dateFactureAt BETWEEN :dateDebut AND :dateFin')
            ->setParameter('dateDebut', $dateDebut)
            ->setParameter('dateFin', $dateFin);
            
        return $qb->getQuery()->getResult();
    }

    /**
     * function qui retourne les recettes journalières des caissières
     *
     * @param DateTime $dateFacture
     * @return void
     */
    public function recetteCaissiereSolde(Datetime $dateFacture)
    {
        $queryBuilder = $this->em->createQueryBuilder();
        $queryBuilder
                ->select('SUM(f.avance) AS SOMME, u.nom AS CAISSIERE, u.id AS id, m.modePaiement AS PAIEMENT, count(f.id) AS NOMBRE, u.photo AS PHOTO')
                ->from(Facture::class, 'f')
                ->innerJoin(User::class, 'u')
                ->innerJoin(ModePaiement::class, 'm')
                ->innerJoin(EtatFacture::class, 'e')
                ->andWhere('f.caissiere = u.id')
                ->andWhere('f.annulee = 0')
                ->andWhere('f.modePaiement = m.id')
                ->andWhere('f.etatFacture = e.id')
                ->andWhere('f.dateFactureAt = :dateFacture')
                ->andWhere('f.avance = f.netAPayer')
                ->andWhere('e.etatFacture = :solde')
                ->setParameter('dateFacture', $dateFacture)
                ->setParameter('solde', ConstantsClass::SOLDE)
                ->groupBy('PAIEMENT', 'CAISSIERE')
                ;

        $query = $queryBuilder->getQuery();

        return $query->execute();
    }

    /**
     * fonction qui affiche les avances du jour
     *
     * @param Datetime $dateFacture
     * @return void
     */
    public function recetteAvanceDuJourCaissiere(Datetime $dateFacture)
    {
        $queryBuilder = $this->em->createQueryBuilder();
        $queryBuilder
                ->select('f.avance AS avance, f.reste AS reste, f.netAPayer AS netAPayer, u.nom AS caissiere, 
                u.id AS id, m.modePaiement AS modePaiement, f.id AS NOMBRE, u.photo AS PHOTO')
                ->from(Facture::class, 'f')
                ->innerJoin(User::class, 'u')
                ->innerJoin(ModePaiement::class, 'm')
                ->innerJoin(EtatFacture::class, 'e')
                ->andWhere('f.caissiere = u.id')
                ->andWhere('f.annulee = 0')
                ->andWhere('f.modePaiement = m.id')
                ->andWhere('f.etatFacture = e.id')
                ->andWhere('f.dateFactureAt = :dateFacture')
                // ->andWhere('f.netAPayer > f.avance ')
                ->setParameter('dateFacture', $dateFacture)
                // ->groupBy('modePaiement', 'caissiere')
                ;

        $query = $queryBuilder->getQuery();

        return $query->execute();
    }


    /**
     * requête qui affiche les recettes du jour non soldés
     *
     * @param DateTime $dateFacture
     * @return void
     */
    public function recetteCaissiereNonSolde(DateTime $dateFacture)
    {
        $queryBuilder = $this->em->createQueryBuilder();
        $queryBuilder
                ->select('SUM(f.avance) AS SOMME, u.nom AS CAISSIERE, u.id AS id, m.modePaiement AS PAIEMENT, count(f.id) AS NOMBRE, u.photo AS PHOTO')
                ->from(Facture::class, 'f')
                ->innerJoin(User::class, 'u')
                ->innerJoin(ModePaiement::class, 'm')
                ->innerJoin(EtatFacture::class, 'e')
                ->andWhere('f.caissiere = u.id')
                ->andWhere('f.annulee = 0')
                ->andWhere('f.modePaiement = m.id')
                ->andWhere('f.etatFacture = e.id')
                ->andWhere('f.dateFactureAt = :dateFacture')
                ->andWhere('f.avance = 0')
                ->andWhere('m.modePaiement = :credit OR m.modePaiement = :prisEncharge')
                ->andWhere('e.etatFacture = :nonSolde')
                ->setParameter('dateFacture', $dateFacture)
                ->setParameter('nonSolde', ConstantsClass::NON_SOLDE)
                ->setParameter('credit', ConstantsClass::CREDIT)
                ->setParameter('prisEncharge', ConstantsClass::PRIS_EN_CHARGE)
                ->groupBy('PAIEMENT', 'CAISSIERE')
                ;

        $query = $queryBuilder->getQuery();

        return $query->execute();
    }


    /**
     * function qui retourne les recettes d'une période
     *
     * @param DateTime $dateDebut
     * @param DateTime $dateFin
     * @return void
     */
    public function recettePeriode(DateTime $dateDebut, DateTime $dateFin)
    {
        $queryBuilder = $this->em->createQueryBuilder();
        $queryBuilder
                ->select('SUM(h.montantAvance) AS SOMME, u.nom AS CAISSIERE, u.id AS id, m.modePaiement AS PAIEMENT, count(f.id) AS NOMBRE, u.photo AS PHOTO')
                ->from(Facture::class, 'f')
                ->innerJoin(User::class, 'u')
                ->innerJoin(ModePaiement::class, 'm')
                ->innerJoin(HistoriquePaiement::class, 'h')
                ->andWhere('f.caissiere = u.id')
                ->andWhere('f.modePaiement = m.id')
                ->andWhere('h.facture = f.id')
                ->andWhere('f.annulee = 0')
                ->andWhere('h.dateAvanceAt BETWEEN :dateDebut AND :dateFin')
                ->setParameter('dateDebut', $dateDebut)
                ->setParameter('dateFin', $dateFin)
                ->groupBy('PAIEMENT', 'CAISSIERE')
                ;

        $query = $queryBuilder->getQuery();

        return $query->execute();
    }


    /**
     * Function retourne le nombre de recette d'une période
     *
     * @param DateTime $dateDebut
     * @param DateTime $dateFin
     * @return void
     */
    public function nombreRecettePeriode(DateTime $dateDebut, DateTime $dateFin)
    {
        $qb = $this->createQueryBuilder('f')
            ->andWhere('f.dateFactureAt BETWEEN :dateDebut AND :dateFin')
            ->andWhere('f.annulee = 0')
            ->setParameter('dateDebut', $dateDebut)
            ->setParameter('dateFin', $dateFin)
            ;
        return $qb->getQuery()->getResult();
    }



    /**
     * function qui retourne les factures à régler
     *
     * @param User $caissiere
     * @return void
     */
    public function getFacturesARegler(User $caissiere)
    {
        $qb = $this->createQueryBuilder('f')
            ->innerJoin('f.etatFacture', 'e')
            ->addSelect('e')
            ->andWhere('f.caissiere = :caissiere')
            ->andWhere('f.etatFacture = e.id')
            ->andWhere('f.annulee = 0')
            ->andWhere('e.etatFacture = :etatFacture')
            ->setParameter('etatFacture', ConstantsClass::NON_SOLDE)
            ->setParameter('caissiere', $caissiere)
            ->orderBy('f.id')
            ;
        return $qb->getQuery()->getResult();
    }

    /**
     * Function qui retourne toutes les recettes
     *
     * @return void
     */
    public function recettes()
    {
        $queryBuilder = $this->em->createQueryBuilder();
        $queryBuilder
                ->select('SUM(f.avance) AS SOMME, u.nom AS CAISSIERE, u.id AS id, m.modePaiement AS PAIEMENT, count(f.id) AS NOMBRE, u.photo AS PHOTO')
                ->from(Facture::class, 'f')
                ->innerJoin(User::class, 'u')
                ->innerJoin(ModePaiement::class, 'm')
                ->andWhere('f.caissiere = u.id')
                ->andWhere('f.annulee = 0')
                ->andWhere('f.modePaiement = m.id')
                ->groupBy('PAIEMENT', 'CAISSIERE')
                ;

        $query = $queryBuilder->getQuery();

        return $query->execute();
    }

    /**
     * fonction qui retourne les factures du jour d'une' caissiere
     *
     * @param User $cassiere
     * @param DateTime $aujourdhui
     * @return void
     */
    public function facturesDuJourCaissiere(User $user, DateTime $aujourdhui)
    {
        $qb = $this->createQueryBuilder('f')
            ->andWhere('f.caissiere = :caissiere')
            ->andWhere('f.dateFactureAt = :aujourdhui')
            ->andWhere('f.annulee = 0')
            ->setParameter('caissiere', $user)
            ->setParameter('aujourdhui', $aujourdhui)
            ;
        return $qb->getQuery()->getResult();
    }

    /**
     * fonction qui retourne les kits vendus du jour par caissiere
     *
     * @return void
     */
    public function kitsVenduParCaissiereDuJour()
    {
        $qb = $this->createQueryBuilder('f')
            ->select('u.nom AS caissiere, mp.modePaiement AS modePaiement, p.libelle AS nomKit, COUNT(ldf.id) AS nombre, SUM(ldf.prixQuantite) AS montant, f.avance AS avance, f.netAPayer AS netAPayer')
            ->innerJoin('f.ligneDeFactures','ldf')
            ->innerJoin('ldf.produit', 'p')
            ->innerJoin('f.caissiere', 'u')
            ->innerJoin('f.modePaiement', 'mp')
            ->andWhere('p.kit = :isKit')
            ->andWhere('f.dateFactureAt = :aujourdhui')
            ->andWhere('mp.modePaiement IN (:modesPaiement)')
            ->setParameter('isKit', true)
            ->setParameter('aujourdhui', new DateTime('today'))
            ->setParameter('modesPaiement', [ConstantsClass::CASH, ConstantsClass::CREDIT, ConstantsClass::PRIS_EN_CHARGE])
            ->groupBy('caissiere', 'modePaiement');

            return $qb->getQuery()->getResult();
    }


    /**
     * fonction qui retourne les kits vendus d'un jour particulier
     *
     * @param DateTime $dateDuJour
     * @return void
     */
    public function kitsVenduParCaissiereDunJourParticulier(DateTime $dateDuJour)
    {
        $qb = $this->createQueryBuilder('f')
            ->select('u.nom AS caissiere, mp.modePaiement AS modePaiement, p.libelle AS nomKit, COUNT(ldf.id) AS nombre, SUM(ldf.prixQuantite) AS montant')
            ->innerJoin('f.ligneDeFactures','ldf')
            ->innerJoin('ldf.produit', 'p')
            ->innerJoin('f.caissiere', 'u')
            ->innerJoin('f.modePaiement', 'mp')
            ->where('p.kit = :isKit')
            ->andWhere('f.dateFactureAt = :dateDuJour')
            ->andWhere('mp.modePaiement IN (:modesPaiement)')
            ->setParameter('isKit', true)
            ->setParameter('dateDuJour', $dateDuJour)
            ->setParameter('modesPaiement', [ConstantsClass::CASH, ConstantsClass::CREDIT, ConstantsClass::PRIS_EN_CHARGE])
            ->groupBy('caissiere', 'modePaiement');

            return $qb->getQuery()->getResult();
    }


    /**
     * fonction qui retourne les kits vendus des caisi_re d'une période données
     *
     * @param DateTime $dateDebut
     * @param DateTime $dateFin
     * @return void
     */
    public function kitsVenduParCaissiereDunePeriode(DateTime $dateDebut, DateTime $dateFin)
    {
        $qb = $this->createQueryBuilder('f')
            ->select('u.nom AS caissiere, mp.modePaiement AS modePaiement, p.libelle AS nomKit, COUNT(ldf.id) AS nombre, SUM(ldf.prixQuantite) AS montant')
            ->innerJoin('f.ligneDeFactures','ldf')
            ->innerJoin('ldf.produit', 'p')
            ->innerJoin('f.caissiere', 'u')
            ->innerJoin('f.modePaiement', 'mp')
            ->where('p.kit = :isKit')
            ->andWhere('f.dateFactureAt BETWEEN :dateDebut AND :dateFin')
            ->andWhere('mp.modePaiement IN (:modesPaiement)')
            ->setParameter('isKit', true)
            ->setParameter('dateDebut', $dateDebut)
            ->setParameter('dateFin', $dateFin)
            ->setParameter('modesPaiement', [ConstantsClass::CASH, ConstantsClass::CREDIT, ConstantsClass::PRIS_EN_CHARGE])
            ->groupBy('caissiere', 'modePaiement', 'nomKit');

            return $qb->getQuery()->getResult();
    }

    /**
     * fonction qui retourne la recettes du jour par caissiere, typeProduit, modePaiement et montant
     *
     * @param DateTime $aujourdhui
     * @return array
     */
    public function recetteDujour(DateTime $aujourdhui): array
    {
        $queryBuilder = $this->em->createQueryBuilder();
        $queryBuilder
                ->select('u.nom AS caissiere', 'tp.typeProduit AS typeProduit', 
                'm.modePaiement AS modePaiement', 'COUNT(tp.typeProduit) AS nombreTypeProduit', 
                'SUM(ldf.prixQuantite) AS montant')
                ->from(Facture::class, 'f')
                ->innerJoin(LigneDeFacture::class, 'ldf')
                ->innerJoin(Produit::class, 'p')
                ->innerJoin(Lot::class, 'lt')
                ->innerJoin(TypeProduit::class, 'tp')
                ->innerJoin(ModePaiement::class, 'm')
                ->innerJoin(EtatFacture::class, 'etf')
                ->innerJoin(User::class, 'u')
                ->andWhere('p.id = ldf.produit')
                ->andWhere('lt.id = p.lot')
                ->andWhere('tp.id = lt.typeProduit')
                ->andWhere('f.id = ldf.facture')
                ->andWhere('m.id = f.modePaiement')
                ->andWhere('etf.id = f.etatFacture')
                ->andWhere('u.id = f.caissiere')
                ->andWhere('p.supprime = 0')
                ->andWhere('p.kit = 0')
                ->andWhere('f.annulee = 0')
                ->andWhere('f.dateFactureAt = :dateFacture')
                ->setParameter('dateFacture', date_format($aujourdhui, 'Y-m-d'))
                ->groupBy('caissiere','typeProduit', 'modePaiement')
                ;

        $query = $queryBuilder->getQuery();

        return $query->execute();
    }



    public function recetteDunePeriode(DateTime $dateDebut, DateTime $dateFin): array
    {
        $queryBuilder = $this->em->createQueryBuilder();
        $queryBuilder
                ->select('u.nom AS caissiere', 'tp.typeProduit AS typeProduit', 
                'm.modePaiement AS modePaiement', 'COUNT(tp.typeProduit) AS nombreTypeProduit', 
                'SUM(ldf.prixQuantite) AS montant')
                ->from(Facture::class, 'f')
                ->innerJoin(LigneDeFacture::class, 'ldf')
                ->innerJoin(Produit::class, 'p')
                ->innerJoin(Lot::class, 'lt')
                ->innerJoin(TypeProduit::class, 'tp')
                ->innerJoin(ModePaiement::class, 'm')
                ->innerJoin(EtatFacture::class, 'etf')
                ->innerJoin(User::class, 'u')
                ->andWhere('p.id = ldf.produit')
                ->andWhere('lt.id = p.lot')
                ->andWhere('tp.id = lt.typeProduit')
                ->andWhere('f.id = ldf.facture')
                ->andWhere('m.id = f.modePaiement')
                ->andWhere('etf.id = f.etatFacture')
                ->andWhere('u.id = f.caissiere')
                ->andWhere('p.supprime = 0')
                ->andWhere('p.kit = 0')
                ->andWhere('f.annulee = 0')
                ->andWhere('f.dateFactureAt BETWEEN :dateDebut AND :dateFin')
                ->setParameter('dateDebut', date_format($dateDebut, 'Y-m-d'))
                ->setParameter('dateFin', date_format($dateFin, 'Y-m-d'))
                ->groupBy('caissiere','typeProduit', 'modePaiement')
                ;

        $query = $queryBuilder->getQuery();

        return $query->execute();
    }



    //    /**
    //     * @return Facture[] Returns an array of Facture objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('f')
    //            ->andWhere('f.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('f.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Facture
    //    {
    //        return $this->createQueryBuilder('f')
    //            ->andWhere('f.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
