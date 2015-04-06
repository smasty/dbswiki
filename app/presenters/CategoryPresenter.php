<?php

namespace App\Presenters;


use App\Forms\BaseForm;
use App\Model\ArticleManager;
use App\Model\CategoryManager;

class CategoryPresenter extends BasePresenter {

    /**
     * @var CategoryManager
     * @inject
     */
    public $categoryManager;


    protected function createComponentCategoryForm(){
        $form = new BaseForm();

        $form->addText("title", "Title:")
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
        if($this->categoryManager->getCategoryByTitle($values->title)){
            $form->addError("Category with this name already exists.");
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
        $this->template->categories = $this->categoryManager->getCategories();
    }

    public function renderTags() {
        $this->template->tags= $this->categoryManager->getTags();
    }


}
