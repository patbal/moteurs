<?php

namespace App\Repository;

use App\Entity\CarnetMoteur;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;

/**
 * @method CarnetMoteur|null find($id, $lockMode = null, $lockVersion = null)
 * @method CarnetMoteur|null findOneBy(array $criteria, array $orderBy = null)
 * @method CarnetMoteur[]    findAll()
 * @method CarnetMoteur[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class CarnetMoteurRepository extends ServiceEntityRepository
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, CarnetMoteur::class);
    }

    // /**
    //  * @return CarnetMoteur[] Returns an array of CarnetMoteur objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('c.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?CarnetMoteur
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
