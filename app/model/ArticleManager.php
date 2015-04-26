<?php

namespace App\Model;

use App\Model\Entity\Article;
use App\Model\Entity\Revision;
use Kdyby\Doctrine\EntityManager;
use Nette;
use Nette\Database\SqlLiteral;


/**
 * Class ArticleManager
 * @package App\Model
 */
class ArticleManager extends BaseManager {


    /**
     * @var TagManager
     */
    private $tagManager;

    /**
     * @var MediaManager
     */
    private $mediaManager;

    /**
     * @var \Kdyby\Doctrine\EntityRepository
     */
    private $repository;

    /**
     * @var \Kdyby\Doctrine\EntityRepository
     */
    private $revisionRepository;


    public function __construct(EntityManager $em, TagManager $tag, MediaManager $media){
        parent::__construct($em);
        $this->repository = $em->getRepository(Article::class);
        $this->revisionRepository = $em->getRepository(Revision::class);
        $this->tagManager = $tag;
        $this->mediaManager = $media;
    }


    public function getAll($limit = NULL, $offset = NULL){
        return $this->repository->createQueryBuilder('a')
            ->addSelect('a, c.title AS cname, c.id AS cid')
            ->leftJoin('a.category', 'c')
            ->leftJoin('a.revision', 'r')
            ->orderBy('a.title')
            ->setMaxResults($limit)
            ->setFirstResult($offset)
            ->getQuery()->getResult();
    }


    public function getCount(){
        return (int) $this->repository->countBy([]);
        //return $this->db->fetchField("SELECT COUNT(*) FROM article");
    }


    public function find($id){
        $article = $this->repository->find($id);
        return $article ?: false;
        /*$row = $this->db->fetch(
            "SELECT a.id, a.title, a.created, r.body, c.title AS cname, c.id AS cid, a.revision_id FROM article a ".
            "LEFT JOIN revision r ON a.revision_id = r.id ".
            "LEFT JOIN category c ON a.category_id = c.id ".
            "WHERE (a.id = ?)", $id
        );
        return $row ? new Article($this->db, $row) : false;*/
    }


    public function searchByTitle($query){
        return $this->repository->createQueryBuilder('a')
            ->addSelect('a, c.title AS cname, c.id AS cid')
            ->leftJoin('a.category', 'c')
            ->leftJoin('a.revision', 'r')
            ->where('a.title LIKE ?1')
            ->orWhere('r.body LIKE ?1')
            ->orderBy('a.title')
            ->setParameter(1, "%$query%")
            ->getQuery()->getResult();
        /*return $this->db->query(
            "SELECT a.id, a.title, a.created, c.title AS cname, c.id AS cid FROM article a ".
            "LEFT JOIN category c ON a.category_id = c.id ".
            "LEFT JOIN revision r ON a.revision_id = r.id ".
            "WHERE a.title ILIKE ? OR r.body ILIKE ? ".
            "ORDER BY a.title", "%$query%", "%$query%"
        );*/
    }


    public function getByTag($tid){
        return $this->repository->createQueryBuilder('a')
            ->addSelect('a, c.title AS cname, c.id AS cid')
            ->leftJoin('a.category', 'c')
            ->leftJoin('a.revision', 'r')
            ->leftJoin('r.tags', 't')
            ->where('t.id = ?1')
            ->orderBy('a.title')
            ->setParameter(1, $tid)
            ->getQuery()->getResult();
    }


    public function getArticleRevision($id, $rev){
        $rows =  $this->revisionRepository->createQueryBuilder('r')
            ->addSelect('author.name AS author_name, author.id AS author_id')
            ->leftJoin('r.article', 'a')
            ->leftJoin('r.author', 'author')
            ->where('r.id = ?1')
            ->andWhere('a.id = ?2')
            ->setParameters([1 => $rev, $id])
            ->getQuery()->getResult();
        return count($rows) > 0 ? $rows[0] : false;
        /*$row = $this->db->fetch(
            "SELECT a.id, a.title, a.created, r.body, c.title AS cname, c.id AS cid, a.revision_id FROM article a ".
            "LEFT JOIN revision r ON a.id = r.article_id ".
            "LEFT JOIN category c ON a.category_id = c.id ".
            "WHERE (a.id = ?) AND (r.id = ?)", $id, $rev
        );
        $revision = $this->db->fetch(
            "SELECT r.*, a.name AS author_name FROM revision r ".
            "LEFT JOIN author a ON a.id = r.author_id  WHERE r.id = ?"
            , $rev);
        return $row ? new Article($this->db, $row, $revision) : false;*/
    }


    public function getRevisions($id, $limit, $offset){
        return $this->revisionRepository->createQueryBuilder('r')
            ->addSelect('a.name AS author_name, a.id AS author_id')
            ->leftJoin('r.author', 'a')
            ->where('IDENTITY(r.article) = ?1')
            ->orderBy('r.created', 'DESC')
            ->setMaxResults($limit)
            ->setFirstResult($offset)
            ->setParameter(1, $id)
            ->getQuery()->getResult();
        /*return $this->db->query(
            "SELECT r.*, a.name AS author_name FROM revision r ".
            "LEFT JOIN author a ON a.id = r.author_id ".
            "WHERE r.article_id = ? ORDER BY r.created DESC LIMIT ? OFFSET ?", $id, $limit, $offset
        );*/

    }

    public function getRevisionCount($id){
        return count($this->find($id)->revisions);
        /*return $this->repository->createQueryBuilder('a')
            ->select('COUNT(a.id)')
            ->leftJoin('a.revisions', 'r')
            ->getQuery()->getResult();*/
        //return $this->db->fetchField("SELECT COUNT(*) FROM revision WHERE article_id = ?", $id);
    }


    public function addArticle($title, $body, $category, $author, array $tags, array $media){

        $this->db->beginTransaction();
        try {
            $aid = $this->insertArticle($title, $category);

            $rid = $this->insertRevision($aid, $body, $author, "New article created.");

            $this->setArticleRevision($aid, $rid);

            // Tags handling
            $allTags = $this->tagManager->getTwistedPairs();
            foreach($tags as $tag){
                if(!isset($allTags[$tag])){
                    $this->tagManager->createTag($tag);
                }
            }

            $allTags = $this->tagManager->getTwistedPairs();
            foreach($tags as $tag){
                $this->tagManager->addRevisionTag($rid, $allTags[$tag]);
            }

            // Media
            foreach($media as $m){
                $this->mediaManager->addRevisionMedia($rid, $m);
            }
        } catch(\Exception $e){
            $this->db->rollBack();
            return false;
        }

        $this->db->commit();
        return $aid;

    }


    public function editArticle($id, $title, $body, $category, $author, array $tags, $log, array $media){

        $this->db->beginTransaction();
        try {
            $this->updateArticle($id, $title, $category);

            $rid = $this->insertRevision($id, $body, $author, $log);

            $this->setArticleRevision($id, $rid);

            // Tags handling
            $allTags = $this->tagManager->getTwistedPairs();
            foreach($tags as $tag){
                if(!isset($allTags[$tag])){
                    $this->tagManager->createTag($tag);
                }
            }

            $allTags = $this->tagManager->getTwistedPairs();
            foreach($tags as $tag){
                $this->tagManager->addRevisionTag($rid, $allTags[$tag]);
            }

            // Media
            foreach($media as $m){
                $this->mediaManager->addRevisionMedia($rid, $m);
            }
        } catch(\Exception $e){
            $this->db->rollBack();
            return false;
        }

        $this->db->commit();
        return true;

    }


    public function deleteArticle($id){
        $this->db->beginTransaction();

        try{
            $revisions = array_values($this->db->fetchPairs("SELECT id FROM revision WHERE article_id = ?", $id));
            $this->db->query("DELETE FROM revision_tag WHERE revision_id IN (?)", $revisions);
            $this->db->query("DELETE FROM revision_media WHERE revision_id IN (?)", $revisions);
            $this->db->query("UPDATE article SET revision_id = NULL WHERE id = ?", $id);
            $this->db->query("DELETE FROM revision WHERE article_id = ?", $id);
            $this->db->query("DELETE FROM article WHERE id = ?", $id);
        } catch(\Exception $e){
            $this->db->rollBack();
            return false;
        }

        $this->db->commit();
        return true;
    }


    public function revertRevision($article, $revision){
        $this->db->beginTransaction();
        try {
            $testId = $this->db->fetchField("SELECT article_id FROM revision WHERE id = ?", $revision);
            if ($testId != $article) {
                return false;
            }
            $this->db->query("UPDATE article SET revision_id = ? WHERE id = ?", $revision, $article);
        } catch(\Exception $e){
            $this->db->rollBack();
            return false;
        }

        $this->db->commit();
        return true;
    }


    public function setArticleRevision($article, $revision){
        $this->db->query("UPDATE article SET revision_id = ? WHERE id = ?", $revision, $article);
    }

    public function insertArticle($title, $category){
        return $this->db->fetchField("INSERT INTO article ? RETURNING id", [
            'title' => $title,
            'created' => new SqlLiteral("NOW()"),
            'category_id' => $category
        ]);
    }

    public function updateArticle($id, $title, $category){
        return $this->db->fetchField("UPDATE article SET ? WHERE (id = ?)", [
            'title' => $title,
            'category_id' => $category
        ], $id);
    }

    public function insertRevision($article, $body, $author, $log){
        return $this->db->fetchField("INSERT INTO revision ? RETURNING id", [
            'created' => new SqlLiteral("NOW()"),
            'log' => $log,
            'body' => $body,
            'article_id' => $article,
            'author_id' => $author
        ]);
    }

}
