<?php

namespace App\Repository;

use App\Entity\Moteur;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;

/**
 * @method Moteur|null find($id, $lockMode = null, $lockVersion = null)
 * @method Moteur|null findOneBy(array $criteria, array $orderBy = null)
 * @method Moteur[]    findAll()
 * @method Moteur[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class MoteurRepository extends ServiceEntityRepository
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, Moteur::class);
    }


    public function findAllOrdered(){
        return $this->createQueryBuilder('m')
            -> orderBy('m.type', 'ASC')
            -> orderBy('m.typeMoteur', 'ASC')
            -> getQuery()
            -> getResult()
        ;
    }


    // /**
    //  * @return Moteur[] Returns an array of Moteur objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('m')
            ->andWhere('m.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('m.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?Moteur
    {
        return $this->createQueryBuilder('m')
            ->andWhere('m.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
