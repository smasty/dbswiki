<?php

namespace App\Presenters;

use App\Forms\BaseForm;
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

        if ($presenter != 'User' && $action != 'login' && !$this->user->isLoggedIn()) {
            if ($this->user->logoutReason === Nette\Http\UserStorage::INACTIVITY) {
                $this->flashMessage('You have been signed out due to inactivity. Please log in again.');
                $this->redirect('User:login', array('backlink' => $this->storeRequest()));
            }
        }

        if($presenter == 'Homepage'){
            return;
        }
        if(!$this->user->isAllowed($presenter) && !$this->user->isAllowed($presenter, $action)){
            $this->flashMessage("You don't have permission to access that page.", 'error');
            $this->redirect('Homepage:');
        }
    }


    protected function createComponentSearchForm(){
        $form = new BaseForm();

        $form->addText("query", "Search query:");

        $form->addSubmit("send", "Search")
            ->setAttribute("class", "hidden");

        $form->onSuccess[] = array($this, 'searchFormSucceeded');
        return $form;
    }

    public function searchFormSucceeded($form, $values){
        if(!isset($values->query) || !trim($values->query)){
            $this->redirect("this");
        }
        $this->redirect("Article:search", ['query' => trim($values->query)]);
    }


}
