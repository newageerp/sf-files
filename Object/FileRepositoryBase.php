<?php

namespace Newageerp\SfFiles\Object;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;

class FileRepositoryBase extends ServiceEntityRepository
{
    public function findByFolder($folder)
    {
        return $this->createQueryBuilder('f')
            ->andWhere('f.folder like :folder')
            ->setParameter('folder', $folder)
            ->orderBy('f.id', 'ASC')
            ->getQuery()
            ->getResult();
    }
}