<?php

namespace App\Model;
use Kdyby\Doctrine\EntityManager;
use Nette;


abstract class BaseManager extends Nette\Object {

    /**
     * @var EntityManager
     */
    protected $em;

    function __construct(EntityManager $em){
        $this->em = $em;
    }


    public function getEntityManager(){
        return $this->em;
    }

}
