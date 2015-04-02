<?php

namespace App\Model;
use Nette;


abstract class BaseManager extends Nette\Object {

    /**
     * @var Nette\Database\Connection
     */
    protected $db;

    function __construct(Nette\Database\Connection $db){
        $this->db = $db;
    }


    public function getConnection(){
        return $this->db;
    }

}