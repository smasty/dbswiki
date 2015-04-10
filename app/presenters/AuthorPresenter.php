<?php

namespace App\Presenters;


use App\Forms\BaseForm;
use App\Model\AuthorManager;

class AuthorPresenter extends BasePresenter {

    /**
     * @var AuthorManager
     * @inject
     */
    public $authorManager;


    public function actionShow($id) {
        $author = $this->authorManager->getInfo($id);
        if(!$author){
            $this->flashMessage("Author with ID $id does not exist or did not author any articles yet.", "error");
            $this->redirect("Author:list");
        }

        $this->template->author = $author;
        $this->template->articles = $this->authorManager->getArticles($id);
    }


    public function renderList(){
        $this->template->authors = $this->authorManager->getInfo();
    }

}
