<?php

namespace App\Presenters;

use Nette,
	App\Model,
    Latte;


/**
 * Base presenter for all application presenters.
 */
abstract class BasePresenter extends Nette\Application\UI\Presenter {


    protected function startup() {
        parent::startup();

        $presenter = $this->getName();
        $action = $this->getAction();

        if($presenter == 'Homepage'){
            return;
        }
        if(!$this->user->isAllowed($presenter) && !$this->user->isAllowed($presenter, $action)){
            $this->flashMessage("You don't have permission to access that page.", 'error');
            $this->redirect('Homepage:');
        }
    }


}
