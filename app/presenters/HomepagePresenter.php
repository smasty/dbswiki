<?php

namespace App\Presenters;

use Nette,
	App\Model;


/**
 * Homepage presenter.
 */
class HomepagePresenter extends BasePresenter{

    /**
     * @var Model\ArticleManager
     * @inject
     */
    public $articleManager;

	public function renderDefault()	{

        
		$this->template->articles = $this->articleManager->getAll();
		$this->template->tags = $this->articleManager->getTagsForArticles();
	}

}
