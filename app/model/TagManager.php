<?php

namespace App\Model;

use App\Model\Entity\Tag;
use Doctrine\ORM\Query\Expr\Join;
use Kdyby\Doctrine\EntityManager;
use Nette;


class TagManager extends BaseManager {

    /**
     * @var \Kdyby\Doctrine\EntityRepository
     */
    private $repository;

    function __construct(EntityManager $em) {
        parent::__construct($em);
        $this->repository = $em->getRepository(Tag::class);
    }


    public function find($id){
        return $this->repository->find($id);
    }


    public function getAll(){
        return $this->repository->createQueryBuilder('t')
            ->addSelect('COUNT(a.id) AS article_count')
            ->leftJoin('t.articles', 'r')
            ->leftJoin('r.article', 'a', Join::WITH, 'IDENTITY(a.revision) = r.id')
            ->where('a.id IS NOT NULL AND t.title != \'\'')
            ->groupBy('t.id')
            ->orderBy('article_count', 'DESC')
            ->addOrderBy('t.title')
            ->getQuery()->getResult();
    }


    public function getPairs(){
        return $this->repository->findPairs('title', ['title' => 'ASC'], 'id');
    }

    public function getTwistedPairs(){
        return $this->repository->findPairs('id', ['title' => 'ASC'], 'title');
    }

    public function createTag($title){
        if(trim($title)){
            $tag = new Tag;
            $tag->title = $title;
            $this->em->persist($tag);
            $this->em->flush();
            //$this->db->query("INSERT INTO tag", ['title' => trim($tag)]);
        }
    }

    public function addRevisionTag($revision, $tag){
        // TODO
        $rev = $this->em->getRepository(Revision::class)->find($revision);
        $tag = $this->repository->find($tag);
        $rev->addMedia($tag);
        $this->em->flush();
        /*$this->db->query("INSERT INTO revision_tag", [
            'revision_id' => $revision,
            'tag_id' => $tag
        ]);*/
    }


}
