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
        /*return $this->db->fetch(
            "SELECT c.*, COUNT(a.id) AS count FROM category c ".
            "LEFT JOIN article a ON a.category_id = c.id ".
            "WHERE c.id = ? ".
            "GROUP BY c.id, c.title, c.description", $id
        );*/
    }


    public function getAll(){
        return $this->repository->createQueryBuilder('c')
            ->addSelect('COUNT(a.id) as article_count')
            ->leftJoin('c.articles', 'a')
            ->orderBy('c.title')
            ->groupBy('c.id')
            ->getQuery()->getResult();
        /*return $this->db->query(
            "SELECT c.title, c.description, c.id, COUNT(a.id) AS count FROM category c ".
            "LEFT JOIN article a ON a.category_id = c.id ".
            "GROUP BY c.id, c.title, c.description ORDER BY c.title"
        );*/
    }


    public function getByTitle($title){
        return $this->repository->findOneBy(['title' => $title]);
    }

    public function getPairs(){
        return $this->repository->findPairs('title', ['title' => 'ASC'], 'id');
        //return $this->db->fetchPairs("SELECT id, title FROM category ORDER BY title");
    }


    public function addCategory($title, $description){
        $this->db->beginTransaction();
        try{
            $this->db->query("INSERT INTO category", [
                'title' => $title,
                'description' => $description
            ]);
        } catch(\Exception $e){
            $this->db->rollBack();
            return false;
        }
        $this->db->commit();
        return true;
    }
}
