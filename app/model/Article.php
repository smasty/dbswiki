<?php
/**
 * Created by PhpStorm.
 * User: smasty
 * Date: 4/3/15
 * Time: 1:03 PM
 */

namespace App\Model;


use Nette;
use Nette\Utils\DateTime;

class Article extends Nette\Object {

    /**
     * @var Nette\Database\Row
     */
    private $row;

    private $db;

    private $tags = null;

    private $revision;

    function __construct(Nette\Database\Connection $db, $row, Nette\Database\Row $revision = null) {
        $this->row = $row;
        $this->db = $db;
        $this->revision = $revision;
    }

    public function getId() {
        return $this->row->id;
    }

    public function getTitle() {
        return $this->row->title;
    }

    public function getCreated() {
        return $this->row->created;
    }

    public function getCategory() {
        return $this->row->cname;
    }

    public function getCategoryId() {
        return $this->row->category_id;
    }

    public function getBody() {
        return $this->row->body;
    }

    public function getRevision() {
        return $this->revision;
    }

    public function getRevisionId() {
        return $this->row->revision_id;
    }

    public function getTags(){
        if($this->tags === NULL){
            $this->tags = $this->db->fetchPairs(
                "SELECT t.id, title FROM revision_tag rt ".
                "LEFT JOIN tag t ON t.id = rt.tag_id ".
                "WHERE rt.revision_id = ?", $this->row->revision_id
            );
        }

        return $this->tags;
    }




}
