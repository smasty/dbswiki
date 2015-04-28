<?php

namespace App\Model;

use App\Model\Entity\Author;
use App\Model\Entity\Revision;
use Exception;
use Kdyby\Doctrine\EntityManager;
use Nette;


class AuthorManager extends BaseManager {

    private $repository;

    function __construct(EntityManager $em) {
        parent::__construct($em);
        $this->repository = $em->getRepository(Author::class);
    }


    /**
     * @return bool|Author
     */
    public function find($id){
        return $this->repository->find($id);
    }

    /**
     * @return bool|Author
     */
    public function getByName($name){
        $row = $this->repository->findOneBy(['name' => $name]);
        return $row ? $row : false;
    }


    /**
     * @return array
     */
    public function getAll(){
        return $this->repository->findBy([], ['name' => 'ASC']);
    }


    public function update($id, array $values){
        if(isset($values['id'])){
            unset($values['id']);
        }
        if(isset($values['password'])){
            $values['password'] =  Authenticator::hash($values['password']);
        }
        $this->db->beginTransaction();
        try {
            $this->db->query("UPDATE author SET ? WHERE id = ?", $values, $id);
        } catch(Exception $e){
            $this->db->rollBack();
            return false;
        }
        $this->db->commit();
        return true;
    }


    public function add($name, $password, $mail, $role){
        $this->db->beginTransaction();
        try{
            $this->db->query(
                "INSERT INTO author (name, password, mail, joined, role) VALUES (?, ?, ?, ?, ?)",
                $name, Authenticator::hash($password), $mail, new Nette\Utils\DateTime(), $role
            );
        } catch(Exception $e){
            $this->db->rollBack();
            return false;
        }
        $this->db->commit();
        return true;
    }


    public function delete($id, $newId){
        $this->db->beginTransaction();
        try{
            $this->db->query("UPDATE revision SET author_id = ? WHERE author_id = ?", $newId, $id);
            $this->db->query("DELETE FROM author WHERE id = ?", $id);

        } catch(Exception $e){
            $this->db->rollBack();
            return false;
        }
        $this->db->commit();
        return true;
    }

    public function getArticles($aid){
        return $this->em->getRepository(Revision::class)->createQueryBuilder('r')
            ->select('a.id, a.title, COUNT(r.id) AS counts, MAX(r.created) AS latest, c.id AS cid, c.title AS cname')
            ->leftJoin('r.article', 'a')
            ->leftJoin('a.category', 'c')
            ->where('IDENTITY(r.author) = ?1')
            ->groupBy('a.id, c.id')
            ->orderBy('counts', 'DESC')
            ->addOrderBy('a.title')
            ->setParameter(1, $aid)
            ->getQuery()->getResult();
    }

    public function getInfo($id = NULL){
        $query = $this->em->getRepository(Revision::class)->createQueryBuilder('r')
            ->select(
                'a.id, a.name, a.mail, a.role, COUNT(DISTINCT r.article) AS articles, COUNT(DISTINCT r.id) AS revisions, '.
                'COUNT(DISTINCT c.id) AS categories, MAX(r.created) AS latest'
            )
            ->leftJoin('r.article', 'p')
            ->leftJoin('p.category', 'c')
            ->leftJoin('r.author', 'a')
            ->groupBy('a.id')->having('COUNT(r.id) > 0')
            ->orderBy('a.name');

        if($id !== NULL){
            $query->where('a.id = ?1')->setParameter(1, $id);
        }

        $result = $query->getQuery()->getResult();
        return $id !== NULL ? (isset($result[0]) ? $result[0] : false) : $result;
    }

}
