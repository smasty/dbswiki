<?php

namespace App\Model;

use Exception;
use Nette;


class CategoryManager extends BaseManager {


    public function find($id){
        return $this->db->fetch(
            "SELECT c.*, COUNT(a.id) AS count FROM category c ".
            "LEFT JOIN article a ON a.category_id = c.id ".
            "WHERE c.id = ? ".
            "GROUP BY c.id", $id
        );
    }


    public function getByTitle($title){
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


    public function getAll(){
        return $this->db->query(
            "SELECT c.title, c.description, c.id, COUNT(a.id) AS count FROM category c ".
            "LEFT JOIN article a ON a.category_id = c.id ".
            "GROUP BY c.id ORDER BY c.title"
        );
    }

    public function getPairs(){
        return $this->db->fetchPairs("SELECT id, title FROM category ORDER BY title");
    }
}
