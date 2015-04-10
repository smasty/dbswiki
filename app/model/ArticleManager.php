<?php

namespace App\Model;

use Nette;
use Nette\Database\SqlLiteral;


class ArticleManager extends BaseManager {


    public function getAll($limit = NULL, $offset = NULL){
        return $this->db->query(
            "SELECT a.id, a.title, a.created, c.title AS cname, c.id AS cid FROM article a ".
            "LEFT JOIN revision r ON a.revision_id = r.id ".
            "LEFT JOIN category c ON a.category_id = c.id ".
            "ORDER BY a.title ".
            ($limit !== NULL ? ("LIMIT $limit" . ($offset !== NULL ? " OFFSET $offset" : "")) : "")
        );
    }


    public function getCount(){
        return $this->db->fetchField("SELECT COUNT(*) FROM article");
    }


    public function find($id){
        $row = $this->db->fetch(
            "SELECT a.id, a.title, a.created, r.body, c.title AS cname, c.id AS cid, a.revision_id FROM article a ".
            "LEFT JOIN revision r ON a.revision_id = r.id ".
            "LEFT JOIN category c ON a.category_id = c.id ".
            "WHERE (a.id = ?)", $id
        );
        return $row ? new Article($this->db, $row) : false;
    }


    public function getByCategory($cid){
        return $this->db->query(
            "SELECT id, title, created FROM article WHERE category_id = ? ".
            "ORDER BY title", $cid
        );
    }


    public function searchByTitle($title){
        return $this->db->query(
            "SELECT a.id, a.title, a.created, c.title AS cname, c.id AS cid FROM article a ".
            "LEFT JOIN category c ON a.category_id = c.id ".
            "WHERE a.title ILIKE ? ".
            "ORDER BY a.title", "%$title%"
        );
    }


    public function getByTag($tid){
        return $this->db->query(
            "SELECT a.id, a.title, a.created, c.id AS cid, c.title AS cname FROM article a ".
            "LEFT JOIN revision_tag rt ON rt.revision_id = a.revision_id ".
            "LEFT JOIN category c ON a.category_id = c.id ".
            "WHERE rt.tag_id = ? ".
            "ORDER BY a.title", $tid
        );
    }


    public function getArticleRevision($id, $rev){
        $row = $this->db->fetch(
            "SELECT a.id, a.title, a.created, r.body, c.title AS category_name, c.id AS category_id, a.revision_id FROM article a ".
            "LEFT JOIN revision r ON a.id = r.article_id ".
            "LEFT JOIN category c ON a.category_id = c.id ".
            "WHERE (a.id = ?) AND (r.id = ?)", $id, $rev
        );
        $revision = $this->db->fetch(
            "SELECT r.*, a.name AS author_name FROM revision r ".
            "LEFT JOIN author a ON a.id = r.author_id  WHERE r.id = ?"
            , $rev);
        return $row ? new Article($this->db, $row, $revision) : false;
    }


    public function getRevisions($id, $limit, $offset){
        return $this->db->query(
            "SELECT r.*, a.name AS author_name FROM revision r ".
            "LEFT JOIN author a ON a.id = r.author_id ".
            "WHERE r.article_id = ? ORDER BY r.created DESC LIMIT ? OFFSET ?", $id, $limit, $offset
        );

    }

    public function getRevisionCount($id){
        return $this->db->fetchField("SELECT COUNT(*) FROM revision WHERE article_id = ?", $id);
    }


    public function addArticle($title, $body, $category, $author, array $tags, array $media){

        $this->db->beginTransaction();
        try {
            $aid = $this->insertArticle($title, $category);

            $rid = $this->insertRevision($aid, $body, $author, "New article created.");

            $this->setArticleRevision($aid, $rid);

            // Tags handling
            $allTags = $this->getAllTags();
            foreach($tags as $tag){
                if(!isset($allTags[$tag])){
                    $this->createTag($tag);
                }
            }

            $allTags = $this->getAllTags();
            foreach($tags as $tag){
                $this->addRevisionTag($rid, $allTags[$tag]);
            }

            // Media
            foreach($media as $m){
                $this->addRevisionMedia($rid, $m);
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
            $allTags = $this->getAllTags();
            foreach($tags as $tag){
                if(!isset($allTags[$tag])){
                    $this->createTag($tag);
                }
            }

            $allTags = $this->getAllTags();
            foreach($tags as $tag){
                $this->addRevisionTag($rid, $allTags[$tag]);
            }

            // Media
            foreach($media as $m){
                $this->addRevisionMedia($rid, $m);
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


    public function getAllTags(){
        return $this->db->fetchPairs("SELECT title, id FROM tag ORDER BY title");
    }


    public function getTagsForArticles($cid = NULL){
        return $this->db->fetchPairs(
            "SELECT a.id, string_agg(t.title, ', ') AS tags FROM article a ".
            "LEFT JOIN revision_tag rt ON a.revision_id = rt.revision_id ".
            "LEFT JOIN tag t ON rt.tag_id = t.id ".
            ($cid !== NULL ? "WHERE a.category_id = $cid " : "") .
            "GROUP BY a.id"
        );
    }


    public function getTagsForArticleRevisions($articleId){
        return $this->db->fetchPairs(
            "SELECT rt.revision_id AS id, string_agg(t.title, ', ') AS tags FROM revision_tag rt ".
            "LEFT JOIN tag t ON t.id = rt.tag_id ".
            "LEFT JOIN revision r ON r.id = rt.revision_id ".
            "WHERE r.article_id = ? GROUP BY rt.revision_id", $articleId);
    }


    protected function setArticleRevision($article, $revision){
        $this->db->query("UPDATE article SET revision_id = ? WHERE id = ?", $revision, $article);
    }

    protected function insertArticle($title, $category){
        return $this->db->fetchField("INSERT INTO article ? RETURNING id", [
            'title' => $title,
            'created' => new SqlLiteral("NOW()"),
            'category_id' => $category
        ]);
    }

    protected function updateArticle($id, $title, $category){
        return $this->db->fetchField("UPDATE article SET ? WHERE (id = ?)", [
            'title' => $title,
            'category_id' => $category
        ], $id);
    }

    protected function insertRevision($article, $body, $author, $log){
        return $this->db->fetchField("INSERT INTO revision ? RETURNING id", [
            'created' => new SqlLiteral("NOW()"),
            'log' => $log,
            'body' => $body,
            'article_id' => $article,
            'author_id' => $author
        ]);
    }

    protected function createTag($tag){
        if(trim($tag)){
            $this->db->query("INSERT INTO tag", ['title' => trim($tag)]);
        }
    }

    protected function addRevisionTag($revision, $tag){
        $this->db->query("INSERT INTO revision_tag", [
            'revision_id' => $revision,
            'tag_id' => $tag
        ]);
    }

    protected function addRevisionMedia($revision, $media){
        $this->db->query("INSERT INTO revision_media", [
            'revision_id' => $revision,
            'media_id' => $media
        ]);
    }

}
