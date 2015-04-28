<?php

namespace App\Model\Entity;


use Kdyby\Doctrine\Entities\Attributes\Identifier;
use Kdyby\Doctrine\Entities\BaseEntity;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 */
class Revision extends BaseEntity{

    use Identifier;

    /**
     * @ORM\Column(type="datetime")
     * @var \DateTime
     */
    protected $created;

    /**
     * @ORM\Column(type="text")
     */
    protected $log;

    /**
     * @ORM\Column(type="text")
     */
    protected $body;

    /**
     * @ORM\ManyToOne(targetEntity="Article", inversedBy="revisions")
     * @var Article
     */
    protected $article;

    /**
     * @ORM\ManyToOne(targetEntity="Author", inversedBy="revisions")
     * @var Author
     */
    protected $author;

    /**
     * @ORM\ManyToMany(targetEntity="Tag", inversedBy="articles")
     * @var \Traversable
     */
    protected $tags;

    /**
     * @ORM\ManyToMany(targetEntity="Media", inversedBy="articles")
     * @var \Traversable
     */
    protected $medias;


    public function toArray(){
        return [
            'id' => $this->id,
            'created' => $this->created,
            'log' => $this->log,
            'body' => $this->body,
        ];
    }


}
