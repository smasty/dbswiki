<?php

namespace App\Presenters;

use App\Model;
use Nette;


/**
 * Homepage presenter.
 */
class HomepagePresenter extends BasePresenter{

    /**
     * @var Model\ArticleManager
     * @inject
     */
    public $articleManager;

    /**
     * @var Model\CategoryManager
     * @inject
     */
    public $categoryManager;

	public function actionDefault()	{

        $vp = new \VisualPaginator($this, 'vp');
        $vp->paginator->itemCount = $this->articleManager->getCount();
        $vp->paginator->itemsPerPage = 10;

		$this->template->articles = $this->articleManager
            ->getAll($vp->paginator->itemsPerPage, $vp->paginator->offset);
		$this->template->tags = $this->articleManager->getTagsForArticles();
	}


    public function renderCategories() {
        $this->template->categories = $this->categoryManager->getCategories();
    }

}
