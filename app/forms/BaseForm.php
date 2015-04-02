<?php

namespace App\Forms;


use Kdyby\BootstrapFormRenderer\BootstrapRenderer;
use Nette;
use Nette\Application\UI\Form;

class BaseForm extends Form {


    function __construct(Nette\ComponentModel\IContainer $parent = NULL, $name = NULL){
        parent::__construct($parent, $name);

        $this->setRenderer(new BootstrapRenderer);
    }
}