<?php

namespace App\Model\Entity;

use Doctrine\ORM\Mapping as ORM;
use Kdyby\Doctrine\Entities\Attributes\Identifier;
use Kdyby\Doctrine\Entities\BaseEntity;

/**
 * @ORM\Entity
 */
class Article extends BaseEntity {

    use Identifier;

    /**
     * @ORM\Column(type="string")
     */
    protected $title;

    /**
     * @ORM\Column(type="datetime")
     * @var \DateTime
     */
    protected $created;

    /**
     * @ORM\ManyToOne(targetEntity="Category", inversedBy="articles")
     * @var Category
     */
    protected $category;

    /**
     * @ORM\ManyToOne(targetEntity="Revision")
     * @var Revision
     */
    protected $revision;

    /**
     * @ORM\OneToMany(targetEntity="Revision", mappedBy="article")
     * @var \Traversable
     */
    protected $revisions;

}
