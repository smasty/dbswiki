<?php

namespace App\Model;

use Exception;
use Nette;


class CategoryManager extends BaseManager {

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


    public function getAllTags(){
        return $this->db->fetchPairs("SELECT id, title FROM tags ORDER BY title");
    }

}
