<?php

namespace App\Model;

use Exception;
use Nette;


class AuthorManager extends BaseManager {


    /**
     * @return bool|Nette\Database\Row
     */
    public function find($id){
        return $this->db->fetch("SELECT * FROM author WHERE id = ?", $id);
    }

    /**
     * @return bool|Nette\Database\Row
     */
    public function getByName($name){
        return $this->db->fetch("SELECT * FROM author WHERE name = ?", $name);
    }


    /**
     * @return Nette\Database\ResultSet
     */
    public function getAll(){
        return $this->db->query("SELECT * FROM author ORDER BY name");
    }


    public function update($id, array $values){
        if(isset($values['id'])){
            unset($values['id']);
        }
        if(isset($values['password'])){
            $values['password'] =  Authenticator::hash($values['password']);
        }
        $this->db->beginTransaction();
        try {
            $this->db->query("UPDATE author SET ? WHERE id = ?", $values, $id);
        } catch(Exception $e){
            $this->db->rollBack();
            return false;
        }
        $this->db->commit();
        return true;
    }


    public function add($name, $password, $mail, $role){
        $this->db->beginTransaction();
        try{
            $this->db->query(
                "INSERT INTO author (name, password, mail, joined, role) VALUES (?, ?, ?, ?, ?)",
                $name, Authenticator::hash($password), $mail, new Nette\Utils\DateTime(), $role
            );
        } catch(Exception $e){
            $this->db->rollBack();
            return false;
        }
        $this->db->commit();
        return true;
    }


    public function delete($id, $newId){
        $this->db->beginTransaction();
        try{
            $this->db->query("UPDATE revision SET author_id = ? WHERE author_id = ?", $newId, $id);
            $this->db->query("DELETE FROM author WHERE id = ?", $id);

        } catch(Exception $e){
            $this->db->rollBack();
            return false;
        }
        $this->db->commit();
        return true;
    }

    public function getArticles($aid){
        return $this->db->query(
            "SELECT a.id, a.title, COUNT(r.id) AS count, MAX(r.created) AS latest, c.id AS cid, c.title AS cname FROM revision r ".
            "LEFT JOIN article a ON r.article_id = a.id ".
            "LEFT JOIN category c ON a.category_id = c.id ".
            "WHERE r.author_id = ? GROUP BY a.id, c.id ORDER BY count DESC, a.title", $aid
        );
    }

    public function getInfo($id = NULL){
        $query = $this->db->query(
            "SELECT a.id, a.name, a.role, a.mail, ".
            "COUNT(DISTINCT p.id) AS articles, COUNT(DISTINCT c.id) AS categories, ".
            "COUNT(r.id) AS revisions, MAX(r.created) AS latest ".
            "FROM author a ".
            "LEFT JOIN revision r ON a.id = r.author_id ".
            "LEFT JOIN article p ON p.revision_id = r.id ".
            "LEFT JOIN category c ON c.id = p.category_id ".
            ($id !== NULL ? "WHERE a.id = $id " : "").
            "GROUP BY a.id HAVING COUNT(r.id) > 0 ORDER BY a.name"
        );

        return $id !== NULL ? $query->fetch() : $query;
    }

}
