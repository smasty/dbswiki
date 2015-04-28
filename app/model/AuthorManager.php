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
        $this->em->beginTransaction();
        try {
            $user = $this->repository->find($id);
            if(!$user){
                return false;
            }
            foreach($values as $k => $v){
                $user->$k = $v;
            }

            $this->em->flush();
            $this->em->commit();
            return true;
        } catch (Exception $e) {
            $this->em->rollback();
            $this->em->close();
            return false;
        }
    }


    public function add($name, $password, $mail, $role){
        $this->em->beginTransaction();
        try{
            $user = new Author();
            $user->name = $name;
            $user->password = $password;
            $user->mail = $mail;
            $user->role = $role;
            $user->joined = new \DateTime();

            $this->em->persist($user);
            $this->em->flush();
            $this->em->commit();
            return true;
        } catch(Exception $e){
            $this->em->rollback();
            $this->em->close();
            return false;
        }
    }


    public function delete($id, $newId){

        $this->em->beginTransaction();
        try {
            $oldAuthor = $this->repository->find($id);
            $newAuthor = $this->repository->find($newId);
            foreach($this->em->getRepository(Revision::class)->findByAuthor($oldAuthor) as $rev){
                $rev->author = $newAuthor;
                $this->em->flush();
            }
            $this->em->remove($oldAuthor);
            $this->em->flush();
            $this->em->commit();
            return true;
        } catch (Exception $e) {
            $this->em->rollback();
            $this->em->close();
            return false;
        }
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
