<?php

namespace App\Model;

use Exception;
use Nette;


class TagManager extends BaseManager {

    public function find($id){
        return $this->db->fetch(
            "SELECT t.*, COUNT(a.id) as count FROM tag t ".
            "JOIN revision_tag rt ON rt.tag_id = t.id ".
            "JOIN article a ON a.revision_id = rt.revision_id ".
            "WHERE t.id = ? GROUP BY t.id", $id
        );
    }


    public function getAll(){
        return $this->db->query(
            "SELECT t.id, t.title, COUNT(a.title) AS count FROM tag t ".
            "LEFT JOIN revision_tag rt ON rt.tag_id = t.id ".
            "LEFT JOIN article a ON a.revision_id = rt.revision_id ".
            "WHERE a.id IS NOT NULL AND t.title != '' ".
            "GROUP BY t.id, t.title ORDER BY count DESC, t.title"
        );
    }


    public function getPairs(){
        return $this->db->fetchPairs("SELECT id, title FROM tag ORDER BY title");
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
