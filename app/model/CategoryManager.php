<?php

namespace App\Model;

use App\Model\Entity\Category;
use Exception;
use Kdyby\Doctrine\EntityManager;
use Nette;


class CategoryManager extends BaseManager {

    /**
     * @var \Kdyby\Doctrine\EntityRepository
     */
    private $repository;

    function __construct(EntityManager $em) {
        parent::__construct($em);
        $this->repository = $em->getRepository(Category::class);
    }


    public function find($id){
        return $this->repository->find($id);
    }


    public function getAll(){
        return $this->repository->createQueryBuilder('c')
            ->addSelect('COUNT(a.id) as article_count')
            ->leftJoin('c.articles', 'a')
            ->orderBy('c.title')
            ->groupBy('c.id')
            ->getQuery()->getResult();
    }


    public function getByTitle($title){
        return $this->repository->findOneBy(['title' => $title]);
    }

    public function getPairs(){
        return $this->repository->findPairs('title', ['title' => 'ASC'], 'id');
    }


    public function addCategory($title, $description){

        $this->em->beginTransaction();
        try {
            $cat = new Category();
            $cat->title = $title;
            $cat->description = $description;

            $this->em->persist($cat);
            $this->em->flush();
            $this->em->commit();
            return true;
        } catch (Exception $e) {
            $this->em->rollback();
            $this->em->close();
            return false;
        }

    }
}
