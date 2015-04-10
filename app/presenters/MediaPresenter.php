<?php

namespace App\Presenters;


use App\Forms\BaseForm;
use App\Model\MediaManager;

class MediaPresenter extends BasePresenter {

    /**
     * @var MediaManager
     * @inject
     */
    public $mediaManager;


    protected function createComponentMediaForm(){
        $form = new BaseForm();

        $form->addText("title", "Title:", 50, 255)
             ->setRequired("Please enter the media title.")
             ->setAttribute("class", "input-xxlarge");

        $form->addUpload("file", "File:")
            ->setRequired("Please select media file.");

        $form->addSelect("type", "Media type:", $this->mediaManager->getMediaTypes())
            ->setRequired("Please select media type");


        $form->addSubmit("send", "Create")
            ->setAttribute("class", "btn-primary btn-large");

        $form->onSuccess[] = array($this, 'createSucceeded');

        return $form;
    }


    public function createSucceeded($form, $values){

        if($this->mediaManager->addMedia($values->title, $values->type, $values->file)){
            $this->flashMessage("Media file $values->title was created successfully.");
            $this->redirect("list");
        } else{
            $form->addError("Could not create a new media file. Sorry.");
            return;
        }
    }


    public function renderList() {
        $this->template->media = $this->mediaManager->getAll();
    }


}
