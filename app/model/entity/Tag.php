<?php
/**
 * Created by PhpStorm.
 * User: smasty
 * Date: 4/24/15
 * Time: 12:59 PM
 */

namespace App\Model\Entity;

use Doctrine\ORM\Mapping as ORM;
use Kdyby\Doctrine\Entities\Attributes\Identifier;
use Kdyby\Doctrine\Entities\BaseEntity;

/**
 * @ORM\Entity()
 */
class Tag extends BaseEntity {

    use Identifier;

    /**
     * @ORM\Column(type="string")
     */
    protected $title;

    /**
     * @ORM\ManyToMany(targetEntity="Revision", mappedBy="tags")
     */
    protected $articles;

}
