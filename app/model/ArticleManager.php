<?php

namespace App\Model;

use Nette;
use Nette\Database\SqlLiteral;


class ArticleManager extends BaseManager {

    public function find($id){
        $row = $this->db->fetch(
            "SELECT a.id, a.title, a.created, r.body, c.title AS category_name, c.id AS category_id, a.revision_id FROM article a ".
            "LEFT JOIN revision r ON a.revision_id = r.id ".
            "LEFT JOIN category c ON a.category_id = c.id ".
            "WHERE (a.id = ?)", $id
        );
        return $row ? new Article($this->db, $row) : false;
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


    public function getRevisions($id){
        return $this->db->query(
            "SELECT r.*, a.name AS author_name FROM revision r ".
            "LEFT JOIN author a ON a.id = r.author_id ".
            "WHERE r.article_id = ? ORDER BY r.created DESC", $id
        );

    }


    public function addArticle($title, $body, $category, $author, array $tags){

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
        } catch(\Exception $e){
            $this->db->rollBack();
            return false;
        }

        $this->db->commit();
        return $aid;

    }


    public function editArticle($id, $title, $body, $category, $author, array $tags, $log){

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


    public function getTagsForArticleRevisions($articleId){
        return $this->db->fetchPairs(
            "SELECT rt.revision_id AS id, string_agg(t.title, ', ') AS tags FROM revision_tag rt ".
            "LEFT JOIN tag t ON t.id = rt.tag_id ".
            "LEFT JOIN revision r ON r.id = rt.revision_id ".
            "WHERE r.article_id = ? GROUP by rt.revision_id", $articleId);
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
        $this->db->query("INSERT INTO tag", ['title' => $tag]);
    }

    protected function addRevisionTag($revision, $tag){
        $this->db->query("INSERT INTO revision_tag", [
            'revision_id' => $revision,
            'tag_id' => $tag
        ]);
    }

}
