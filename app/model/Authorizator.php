<?php

use Nette\Security,
    Nette\Utils\Strings;


/**
 * Users authorizator.
 */
class Authorizator extends Nette\Security\Permission
{
    public function __construct()
    {
        $this->addRole('guest');
        $this->addRole('user');
        $this->addRole('admin');

        /*$this->addResource('Points');
        $this->addResource('Edit');
        $this->addResource('Admin');

        $this->allow('guest', 'Points');

        $this->allow('animator', 'Points');
        $this->allow('animator', 'Edit');*/

        $this->allow('admin');
    }

}
