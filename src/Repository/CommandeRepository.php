<?php

namespace App\Repository;

use App\Entity\Commande;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Commande>
 */
class CommandeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Commande::class);
    }

    //    /**
    //     * @return Commande[] Returns an array of Commande objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('c')
    //            ->andWhere('c.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('c.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Commande
    //    {
    //        return $this->createQueryBuilder('c')
    //            ->andWhere('c.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }

    // Dans CommandeRepository.php
    public function findAllWithClient()
    {
        return $this->createQueryBuilder('c')
            ->leftJoin('c.client', 'client')
            ->addSelect('client')
            ->getQuery()
            ->getResult();
    }
    public function findClientNameByCommande(int $commandeId): ?string
    {
        return $this->createQueryBuilder('c')
            ->select('client.nom')
            ->leftJoin('c.client', 'client')
            ->where('c.id = :id')
            ->setParameter('id', $commandeId)
            ->getQuery()
            ->getSingleScalarResult();
    }
}
