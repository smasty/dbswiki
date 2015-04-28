<?php

namespace App\Model;

use App\Model\Entity\Article;
use App\Model\Entity\Author;
use App\Model\Entity\Category;
use App\Model\Entity\Revision;
use Kdyby\Doctrine\EntityManager;
use Nette;


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
    }


    public function find($id){
        $article = $this->repository->find($id);
        return $article ?: false;
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

    }

    public function getRevisionCount($id){
        return count($this->find($id)->revisions);
    }


    public function addArticle($title, $body, $category, $author, array $tags, array $media){

        $this->em->beginTransaction();
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

            $this->em->flush();
            $this->em->commit();
            return $aid;
        } catch(\Exception $e){
            $this->em->rollback();
            return false;
        }

    }


    public function editArticle($id, $title, $body, $category, $author, array $tags, $log, array $media){

        $this->em->beginTransaction();
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

            $this->em->flush();
            $this->em->commit();
            return true;
        } catch(\Exception $e){
            $this->em->rollback();
            return false;
        }

    }


    public function deleteArticle($id){
        $this->em->beginTransaction();
        try {
            $article = $this->repository->find($id);
            $article->revision = NULL;
            $this->em->flush();

            foreach($this->revisionRepository->findByArticle($article) as $revision){
                foreach($revision->tags as $tag){
                    $revision->removeTag($tag);
                }
                foreach($revision->medias as $media){
                    $revision->removeMedia($media);
                }
                $this->em->flush();
                $this->em->remove($revision);
                $this->em->flush();
            }
            $this->em->remove($article);

            $this->em->flush();
            $this->em->commit();
            return true;
        } catch (\Exception $e) {
            $this->em->rollback();
            $this->em->close();
            return false;
        }
    }


    public function revertRevision($article_id, $revision_id){
        $this->em->beginTransaction();
        try {
            $article = $this->repository->find($article_id);
            $revision = $this->revisionRepository->find($revision_id);
            if($revision->article != $article){
                return false;
            }

            $article->revision = $revision;

            $this->em->flush();
            $this->em->commit();
            return true;
        } catch (\Exception $e) {
            $this->em->rollback();
            $this->em->close();
            return false;
        }
    }


    public function setArticleRevision($article_id, $revision_id){
        $article = $this->repository->find($article_id);
        $revision = $this->revisionRepository->find($revision_id);

        $article->revision = $revision;
        $this->em->flush();
    }

    public function insertArticle($title, $category){
        $article = new Article();
        $article->title = $title;
        $article->created = new \DateTime();
        $article->category = $this->em->getRepository(Category::class)->find($category);

        $this->em->persist($article);
        $this->em->flush();
        return $article->id;
    }

    public function updateArticle($id, $title, $category){
        $article = $this->repository->find($id);
        $article->title = $title;
        $article->category = $this->em->getRepository(Category::class)->find($category);

        $this->em->flush();
        return $article->id;
    }

    public function insertRevision($article_id, $body, $author, $log){
        $revision = new Revision();
        $revision->created = new \DateTime();
        $revision->log = $log;
        $revision->body = $body;
        $revision->article = $this->repository->find($article_id);
        $revision->author = $this->em->getRepository(Author::class)->find($author);

        $this->em->persist($revision);
        $this->em->flush();
        return $revision->id;
    }

}
