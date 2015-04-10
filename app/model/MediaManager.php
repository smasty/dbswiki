<?php

namespace App\Model;

use Exception;
use Nette;


class MediaManager extends BaseManager {

    private $mediaTypes;
    private $targetPath;

    public function __construct(array $mediaTypes, $targetPath){
        $this->mediaTypes = $mediaTypes;
        $this->targetPath = __DIR__ . "/" . $targetPath;
    }


    public function getMediaTypes(){
        return $this->mediaTypes;
    }


    public function addMedia($title, $type, Nette\Http\FileUpload $file){

    }

}
