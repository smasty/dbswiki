<?php

namespace App\Model\Entity;

use Doctrine\ORM\Mapping as ORM;
use Kdyby\Doctrine\Entities\Attributes\Identifier;
use Kdyby\Doctrine\Entities\BaseEntity;

/**
 * @ORM\Entity()
 */
class Author extends BaseEntity {

    use Identifier;

    /**
     * @ORM\Column(type="string")
     */
    protected $name;

    /**
     * @ORM\Column(type="string")
     */
    protected $password;

    /**
     * @ORM\Column(type="string")
     */
    protected $mail;

    /**
     * @ORM\Column(type="datetime")
     * @var \DateTime
     */
    protected $joined;

    /**
     * @ORM\Column(type="string")
     */
    protected $role;

    /**
     * @ORM\OneToMany(targetEntity="Revision", mappedBy="author")
     * @var \Traversable
     */
    protected $revisions;


    public function toArray(){
        return [
            'id' => $this->id,
            'name' => $this->name,
            'password' => $this->password,
            'mail' => $this->mail,
            'joined' => $this->joined,
            'role' => $this->role,
        ];
    }

}
