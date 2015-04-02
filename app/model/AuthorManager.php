<?php

namespace App\Model;

use Exception;
use Nette;


class AuthorManager extends BaseManager {


    /**
     * @return bool|Nette\Database\Row
     */
    public function find($id){
        return $this->db->query("SELECT * FROM author WHERE id = ?", $id)->fetch();
    }

    /**
     * @return bool|Nette\Database\Row
     */
    public function getByName($name){
        return $this->db->query("SELECT * FROM author WHERE name = ?", $name)->fetch();
    }


    /**
     * @return Nette\Database\ResultSet
     */
    public function getAll(){
        return $this->db->query("SELECT * FROM author");
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


    public function delete($id){
        $this->db->beginTransaction();
        try{
            $this->db->query("DELETE FROM author WHERE id = ?", $id);
        } catch(Exception $e){
            $this->db->rollBack();
            return false;
        }
        $this->db->commit();
        return true;
    }

}
