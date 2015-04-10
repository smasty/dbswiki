<?php

namespace App\Model;

use Nette\Security;


/**
 * Users authorizator.
 */
class Authorizator extends Security\Permission {

    public function __construct(){
        $this->addRole('guest');
        $this->addRole('user', 'guest');
        $this->addRole('admin');

        $this->addResource('Homepage');
        $this->allow('guest', 'Homepage');

        $this->addResource('User');
        $this->allow('guest', 'User', ['login', 'register']);
        $this->allow('user', 'User', 'logout');
        $this->deny('user', 'User', 'register');

        $this->addResource('Article');
        $this->allow('guest', 'Article', ['show', 'history', 'category', 'tag', 'search']);
        $this->allow('user', 'Article', ['edit', 'create', 'revert']);

        $this->addResource('Category');
        $this->allow('guest', 'Category', ['show']);
        $this->allow('user', 'Category', ['edit', 'delete', 'create']);

        $this->addResource('Media');
        $this->allow('guest', 'Media', ['list']);
        $this->allow('user', 'Media', ['edit', 'delete', 'create']);

        $this->addResource('Author');
        $this->allow('guest', 'Author', ['show', 'list']);

        $this->allow('admin');

    }

}
