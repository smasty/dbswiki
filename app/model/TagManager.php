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
        /*return $this->db->fetch(
            "SELECT t.*, COUNT(a.id) as count FROM tag t ".
            "JOIN revision_tag rt ON rt.tag_id = t.id ".
            "JOIN article a ON a.revision_id = rt.revision_id ".
            "WHERE t.id = ? GROUP BY t.id, t.title", $id
        );*/
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

        /*return $this->db->query(
            "SELECT t.id, t.title, COUNT(a.title) AS count FROM tag t ".
            "LEFT JOIN revision_tag rt ON rt.tag_id = t.id ".
            "LEFT JOIN article a ON a.revision_id = rt.revision_id ".
            "WHERE a.id IS NOT NULL AND t.title != '' ".
            "GROUP BY t.id, t.title ORDER BY count DESC, t.title"
        );*/
    }


    public function getPairs(){
        return $this->repository->findPairs('title', ['title' => 'ASC'], 'id');
    }

    public function getTwistedPairs(){
        return $this->repository->findPairs('id', ['title' => 'ASC'], 'title');
    }

    public function createTag($tag){
        if(trim($tag)){
            $this->db->query("INSERT INTO tag", ['title' => trim($tag)]);
        }
    }

    public function addRevisionTag($revision, $tag){
        $this->db->query("INSERT INTO revision_tag", [
            'revision_id' => $revision,
            'tag_id' => $tag
        ]);
    }


}
