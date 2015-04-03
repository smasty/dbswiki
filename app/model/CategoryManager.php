<?php

namespace App\Model;

use Exception;
use Nette;


class CategoryManager extends BaseManager {

    public function getAllCategories(){
        return $this->db->fetchPairs("SELECT id, title FROM category ORDER BY title");
    }


    public function getAllTags(){
        return $this->db->fetchPairs("SELECT id, title FROM tags ORDER BY title");
    }

}
