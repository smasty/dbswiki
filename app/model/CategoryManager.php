<?php

namespace App\Model;

use Exception;
use Nette;


class CategoryManager extends BaseManager {


    public function findCategory($id){
        return $this->db->fetch(
            "SELECT c.*, COUNT(a.id) AS count FROM category c ".
            "LEFT JOIN article a ON a.category_id = c.id ".
            "WHERE c.id = ? ".
            "GROUP BY c.id", $id
        );
    }


    public function findTag($id){
        return $this->db->fetch(
            "SELECT t.*, COUNT(a.id) as count FROM tag t ".
            "JOIN revision_tag rt ON rt.tag_id = t.id ".
            "JOIN article a ON a.revision_id = rt.revision_id ".
            "WHERE t.id = ? GROUP BY t.id", $id
        );
    }


    public function getCategoryByTitle($title){
        return $this->db->fetch("SELECT id FROM category WHERE title = ?", $title);
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


    public function getCategories(){
        return $this->db->query(
            "SELECT c.title, c.description, c.id, COUNT(a.id) AS count FROM category c ".
            "LEFT JOIN article a ON a.category_id = c.id ".
            "GROUP BY c.id ORDER BY c.title"
        );
    }

    public function getCategoriesPairs(){
        return $this->db->fetchPairs("SELECT id, title FROM category ORDER BY title");
    }


    public function getTags(){
        return $this->db->query(
            "SELECT t.id, t.title, COUNT(a.title) AS count FROM tag t ".
            "LEFT JOIN revision_tag rt ON rt.tag_id = t.id ".
            "LEFT JOIN article a ON a.revision_id = rt.revision_id ".
            "WHERE a.id IS NOT NULL AND t.title != '' ".
            "GROUP BY t.id, t.title ORDER BY count DESC, t.title"
        );
    }


    public function getTagsPairs(){
        return $this->db->fetchPairs("SELECT id, title FROM tag ORDER BY title");
    }

}
