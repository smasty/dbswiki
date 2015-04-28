<?php

namespace App\Presenters;


use App\Forms\BaseForm;
use App\Model\TagManager;
use App\Model\CategoryManager;

class CategoryPresenter extends BasePresenter {

    /**
     * @var CategoryManager
     * @inject
     */
    public $categoryManager;

    /**
     * @var TagManager
     * @inject
     */
    public $tagManager;


    protected function createComponentCategoryForm(){
        $form = new BaseForm();

        $form->addText("title", "Title:", 20, 255)
            ->setRequired("Please enter a category title.")
            ->setAttribute("class", "input-xlarge");

        $form->addTextArea("description", "Description:", 100, 3)
            ->setRequired("Please enter a category description.")
            ->setAttribute("class", "input-xxlarge");

        $form->addSubmit("send", "Create")
            ->setAttribute("class", "btn-primary btn-large");

        $form->onSuccess[] = array($this, 'createSucceeded');

        return $form;
    }


    public function createSucceeded($form, $values){
        if($this->categoryManager->getByTitle($values->title)){
            $form->addError("Category with this name already exists.", "error");
            return;
        }

        if($this->categoryManager->addCategory($values->title, $values->description)){
            $this->flashMessage("Category $values->title was created successfully.");
            $this->redirect("list");
        } else{
            $form->addError("Could not create a new category. Sorry.");
            return;
        }
    }


    public function renderList() {
        $this->template->categories = $this->categoryManager->getAll();
    }

    public function renderTags() {
        $this->template->tags= $this->tagManager->getAll();
    }


}
