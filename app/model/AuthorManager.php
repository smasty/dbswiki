<?php

namespace App\Model;

use Nette;
use Nette\Utils\Strings;


class AuthorManager extends BaseManager {

    /**
     * @param string $name
     * @return bool|Nette\Database\Row
     */
    public function getByName($name){
        return $this->db->query("SELECT * FROM author WHERE name = ? OR mail = ?", $name, $name)->fetch();
    }


    public static function hash($password){
        if ($password === Strings::upper($password)){
            $password = Strings::lower($password);
        }
        return md5($password);
    }

}
