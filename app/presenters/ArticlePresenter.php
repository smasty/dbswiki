<?php

namespace App\Presenters;


use App\Forms\BaseForm;
use App\Model\ArticleManager;
use App\Model\CategoryManager;
use App\Model\TagManager;
use App\Model\MediaManager;

class ArticlePresenter extends BasePresenter {

    /**
     * @var ArticleManager
     * @inject
     */
    public $articleManager;

    /**
     * @var CategoryManager
     * @inject
     */
    public $categoryManager;

    /**
     * @var MediaManager
     * @inject
     */
    public $mediaManager;

    /**
     * @var TagManager
     * @inject
     */
    public $tagManager;


    public function renderShow($id, $rev = NULL){
        if($rev !== NULL){
            $revision = $this->articleManager->getArticleRevision($id, $rev);
            if(!$revision){
                $this->flashMessage("Incorrect revision ID #$rev.", "error");
                $this->redirect("show", $id, null);
            }
            $this->template->revision = $revision;
            $article = $revision[0]->article;
        } else {
            $article = $this->articleManager->find($id);
        }
        if($article === false){
            $this->flashMessage("Article with ID $id does not exist.", "error");
            $this->redirect("Homepage:");
        }

        $this->template->article = $article;
        $this->template->media = $this->mediaManager->getByRevision($article->revision->id);
    }




    public function actionCreate(){
        $this['articleForm']->addSubmit('send', 'Create')->setAttribute('class', 'btn-primary btn-large');
        $this['articleForm']->onSuccess[] = array($this, 'createSucceeded');
    }


    protected function createComponentArticleForm(){
        $form = new BaseForm;

        $form->addText('title', 'Title:', 20, 255)
             ->setRequired('Please specify title.')
             ->setAttribute('class', 'input-xxlarge');

        $form->addTextArea('body', 'Body (Markdown):', 100, 15)
             ->setRequired('Please enter the body.')->setAttribute('class', 'input-block-level');

        $form->addSelect('category', 'Category:', $this->categoryManager->getPairs())
             ->setPrompt('-- Select --');

        $form->addText('tags', 'Tags (comma-separated):')->setAttribute('class', 'input-xxlarge');

        $form->addCheckboxList("media", "Attach media:", $this->mediaManager->getPairs());

        return $form;
    }


    public function createSucceeded($form, $values){

        $tags = explode(',', $values->tags);
        foreach($tags as $k => $v){
            $tags[$k] = trim($v);
        }

        if($id = $this->articleManager->addArticle($values->title, $values->body, $values->category,
        $this->user->id, $tags, $values->media)){
            $this->flashMessage("Article created successfully.");
            $this->redirect("show", $id);
        } else{
            $form->addError("Article creation failed. Sorry.");
        }
    }




    public function actionEdit($id){
        $article = $this->articleManager->find($id);
        if($article === false){
            $this->flashMessage("Article with ID $id does not exist.", "error");
            $this->redirect("Homepage:");
        }

        $this->template->article = $article;


        $this['articleForm']->addTextarea('log', "Revision summary", 100, 2)
            ->setRequired("Revision summary is required.")
            ->setAttribute('class', 'input-xxlarge');

        $tags = [];
        foreach($article->revision->tags as $t){
            $tags[] = $t->title;
        }
        $media = [];
        foreach($article->revision->medias as $m){
            $media[] = $m->id;
        }
        $this['articleForm']->setDefaults([
            'title' => $article->title,
            'body' => $article->revision->body,
            'category' => $article->category->id,
            'tags' => implode(", ", $tags),
            'media' => $media,
        ]);

        $this['articleForm']->addSubmit('send2', 'Edit')->setAttribute('class', 'btn-primary btn-large');
        $this['articleForm']->onSuccess[] = array($this, 'editSucceeded');
    }

    public function editSucceeded($form, $values){

        $tags = explode(',', $values->tags);
        foreach($tags as $k => $v){
            $tags[$k] = trim($v);
        }

        if($this->articleManager->editArticle(
            $this->getParameter('id'), $values->title, $values->body, $values->category,
            $this->user->id, $tags, $values->log, $values->media
        )){
            $this->flashMessage("Article edited successfully. New revision was created.");
            $this->redirect("show", $this->getParameter('id'));
        } else{
            $form->addError("Editing article failed. Sorry.");
        }
    }




    public function actionDelete($id){
        $article = $this->articleManager->find($id);
        if($article === false){
            $this->flashMessage("Article with ID $id does not exist.", "error");
            $this->redirect("Homepage:");
        }

        if($this->articleManager->deleteArticle($id)){
            $this->flashMessage("Article and all it's revisions were deleted.");
            $this->redirect("Homepage:");
        } else{
            $this->flashMessage("Could not delete article. Sorry", "error");
            $this->redirect("Homepage:");
        }
    }



    public function actionHistory($id){
        $article = $this->articleManager->find($id);
        if($article === false){
            $this->flashMessage("Article with ID $id does not exist.");
            $this->redirect("Homepage:");
        }
        $vp = new \VisualPaginator($this, 'vp');
        $vp->paginator->itemCount = $this->articleManager->getRevisionCount($id);
        $vp->paginator->itemsPerPage = 10;

        $this->template->article = $article;
        $this->template->revisions = $this->articleManager->getRevisions($id, $vp->paginator->itemsPerPage, $vp->paginator->offset);
    }


    public function actionRevert($id, $rev){
        $article = $this->articleManager->find($id);
        if($article === false){
            $this->flashMessage("Article with ID $id does not exist.", "error");
            $this->redirect("Homepage:");
        }

        if($this->articleManager->revertRevision($id, $rev)){
            $this->flashMessage("Article was reverted to revision #$rev.");
            $this->redirect("show", $id);
        } else{
            $this->flashMessage("Could not revert to revision #$rev. Sorry.", "error");
            $this->redirect("history", $id);
        }
    }


    public function actionCategory($id){
        $category = $this->categoryManager->find($id);

        if(!$category){
            $this->flashMessage("Category with ID $id does not exist.", "error");
            $this->redirect("Category:list");
        }

        $this->template->category = $category;
        $this->template->articles = $category->articles;
        //$this->template->tags = $this->tagManager->getTagsForArticles($id);
    }


    public function actionTag($id){
        $tag = $this->tagManager->find($id);

        if(!$tag){
            $this->flashMessage("Tag with ID $id does not exist.", "error");
            $this->redirect("Category:tags");
        }

        $this->template->tag = $tag;
        $this->template->articles = $this->articleManager->getByTag($id);
    }


    public function actionSearch($query){
        $query = trim($query);
        if(!$query){
            $this->redirect("Homepage:");
        }
        $this->template->query = $query;
        $this->template->articles = $this->articleManager->searchByTitle($query);
    }


}
