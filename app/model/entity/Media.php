<?php

namespace App\Model\Entity;

use Doctrine\ORM\Mapping as ORM;
use Kdyby\Doctrine\Entities\Attributes\Identifier;
use Kdyby\Doctrine\Entities\BaseEntity;


/**
 * @ORM\Entity()
 */
class Media extends BaseEntity {

    use Identifier;

    /**
     * @ORM\Column(type="string")
     */
    protected $title;

    /**
     * @ORM\Column(type="string", length=32)
     */
    protected $type;

    /**
     * @ORM\Column(type="text")
     */
    protected $path;

    /**
     * @ORM\Column(type="datetime")
     * @var \DateTime
     */
    protected $created;

    /**
     * @ORM\ManyToMany(targetEntity="Revision", mappedBy="medias")
     */
    protected $articles;

}
