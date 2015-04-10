<?php

namespace App\Model;

use Exception;
use Nette;


class MediaManager extends BaseManager {

    private $mediaTypes;
    private $targetPath;

    public function __construct(Nette\Database\Connection $conn, array $mediaTypes, $targetPath){
        parent::__construct($conn);
        $this->mediaTypes = $mediaTypes;
        $this->targetPath = $targetPath;
    }


    public function getMediaTypes(){
        return $this->mediaTypes;
    }


    public function getType($type){
        return isset($this->mediaTypes[$type]) ? $this->mediaTypes[$type] : $type;
    }


    public function getAll(){
        return $this->db->query("SELECT * FROM media ORDER BY title");
    }

    public function getPairs(){
        return $this->db->fetchPairs("SELECT id, title FROM media ORDER BY title");
    }


    public function getByRevision($rid){
        return $this->db->query(
            "SELECT m.title, m.path, m.type FROM revision_media rm ".
            "LEFT JOIN media m ON rm.media_id = m.id ".
            "WHERE rm.revision_id = ? ORDER BY m.title", $rid
        );
    }


    public function addMedia($title, $type, Nette\Http\FileUpload $file){
        if(!$file->isOk()){
            return false;
        }

        $fileName = $file->getSanitizedName();
        $dir =  __DIR__ . "/../../www/" . $this->targetPath;
        $file->move($dir . "/" . $fileName);

        $dbPath = $this->targetPath . "/" . $fileName;

        $this->db->beginTransaction();
        try {
            $this->db->query("INSERT INTO media ", [
                'title' => $title,
                'type' => $type,
                'path' => $dbPath,
                'created' => new Nette\Database\SqlLiteral("NOW()")
            ]);
        } catch(\Exception $e){
            $this->db->rollBack();
            return false;
        }
        $this->db->commit();
        return true;
    }

}
