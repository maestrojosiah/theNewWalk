<?php

namespace AppBundle\Repository;

/**
 * ArticleRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class ArticleRepository extends \Doctrine\ORM\EntityRepository
{

    public function searchMatchingPosts($searchText)
    {
    	return $this->createQueryBuilder('p')
               ->where('p.title LIKE :input OR p.body LIKE :input OR p.description LIKE :input')
               ->setParameter('input', '%' .$searchText.'%')
               ->setMaxResults(10)
               ->getQuery()
               ->getResult();
    }

}
