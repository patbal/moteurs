<?php

namespace App\Repository;

use App\Entity\Testmoteur;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;

/**
 * @method Testmoteur|null find($id, $lockMode = null, $lockVersion = null)
 * @method Testmoteur|null findOneBy(array $criteria, array $orderBy = null)
 * @method Testmoteur[]    findAll()
 * @method Testmoteur[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class TestmoteurRepository extends ServiceEntityRepository
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, Testmoteur::class);
    }

    // /**
    //  * @return Testmoteur[] Returns an array of Testmoteur objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('t')
            ->andWhere('t.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('t.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?Testmoteur
    {
        return $this->createQueryBuilder('t')
            ->andWhere('t.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
