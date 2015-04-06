<?php

namespace App\Presenters;


use App\Forms\BaseForm;
use App\Model\ArticleManager;
use App\Model\CategoryManager;

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


    public function renderShow($id, $rev = NULL){
        if($rev !== NULL){
            $article = $this->articleManager->getArticleRevision($id, $rev);
            if($article === false){
                $this->flashMessage("Incorrect revision ID #$rev.", "error");
                $this->redirect("show", $id, null);
            }
            $this->template->revision = $article->revision;
        } else {
            $article = $this->articleManager->find($id);
        }
        if($article === false){
            $this->flashMessage("Article with ID $id does not exist.", "error");
            $this->redirect("Homepage:");
        }

        $this->template->article = $article;
    }




    public function actionCreate(){
        $this['articleForm']->addSubmit('send', 'Create')->setAttribute('class', 'btn-primary btn-large');
        $this['articleForm']->onSuccess[] = array($this, 'createSucceeded');
    }


    protected function createComponentArticleForm(){
        $form = new BaseForm;

        $form->addText('title', 'Title:')
             ->setRequired('Please specify title.')
             ->setAttribute('class', 'input-xxlarge');

        $form->addTextArea('body', 'Body (Markdown):', 100, 15)
             ->setRequired('Please enter the body.')->setAttribute('class', 'input-block-level');

        $form->addSelect('category', 'Category:', $this->categoryManager->getCategoriesPairs())
             ->setPrompt('-- Select --');

        $form->addText('tags', 'Tags (comma-separated):')->setAttribute('class', 'input-xxlarge');

        return $form;
    }


    public function createSucceeded($form, $values){

        $tags = explode(',', $values->tags);
        foreach($tags as $k => $v){
            $tags[$k] = trim($v);
        }

        if($id = $this->articleManager->addArticle($values->title, $values->body, $values->category,
        $this->user->id, $tags)){
            $this->flashMessage("Article created successfully.");
            $this->redirect("show", $id);
        } else{
            $form->addError("Article creation failed. Sorry.");
        }
    }




    public function actionEdit($id){
        $article = $this->articleManager->find($id);
        if($article === false){
            $this->flashMessage("Article with ID $id does not exist.");
            $this->redirect("Homepage:");
        }

        $this->template->article = $article;


        $this['articleForm']->addTextarea('log', "Revision summary", 100, 2)
            ->setRequired("Revision summary is required.")
            ->setAttribute('class', 'input-xxlarge');

        $this['articleForm']->setDefaults([
            'title' => $article->title,
            'body' => $article->body,
            'category' => $article->categoryId,
            'tags' => implode(", ", $article->tags),
        ]);

        $this['articleForm']->addSubmit('send2', 'Edit')->setAttribute('class', 'btn-primary btn-large');
        $this['articleForm']->onSuccess[] = array($this, 'editSucceeded');
    }

    // TODO listings for author
    // TODO media
    // TODO search (title, author, date)
    // TODO statistics

    public function editSucceeded($form, $values){

        $tags = explode(',', $values->tags);
        foreach($tags as $k => $v){
            $tags[$k] = trim($v);
        }

        if($this->articleManager->editArticle(
            $this->getParameter('id'), $values->title, $values->body, $values->category,
            $this->user->id, $tags, $values->log
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
            $this->flashMessage("Article with ID $id does not exist.");
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
        $this->template->tags = $this->articleManager->getTagsForArticleRevisions($id);
    }


    public function actionRevert($id, $rev){
        $article = $this->articleManager->find($id);
        if($article === false){
            $this->flashMessage("Article with ID $id does not exist.");
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
        $category = $this->categoryManager->findCategory($id);

        if(!$category){
            $this->flashMessage("Category with ID $id does not exist.");
            $this->redirect("Homepage:");
        }

        $this->template->category = $category;
        $this->template->articles = $this->articleManager->getByCategory($id);
        $this->template->tags = $this->articleManager->getTagsForArticles($id);
    }


    public function actionTag($id){
        $tag = $this->categoryManager->findTag($id);

        if(!$tag){
            $this->flashMessage("Tag with ID $id does not exist.");
            $this->redirect("Homepage:");
        }

        $this->template->tag = $tag;
        $this->template->articles = $this->articleManager->getByTag($id);
    }


}
