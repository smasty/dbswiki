<?php

namespace App\Model;

use App\Model\Entity\Media;
use Exception;
use Kdyby\Doctrine\EntityManager;
use Kdyby\GeneratedProxy\__CG__\App\Model\Entity\Revision;
use Nette;


class MediaManager extends BaseManager {

    private $mediaTypes;
    private $targetPath;

    /**
     * @var \Kdyby\Doctrine\EntityRepository
     */
    private $repository;

    public function __construct(EntityManager $em, array $mediaTypes, $targetPath){
        parent::__construct($em);
        $this->repository = $em->getRepository(Media::class);
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
        return $this->repository->findBy([], ['title' => 'ASC']);
    }

    public function getPairs(){
        return $this->repository->findPairs('title', ['title' => 'ASC'], 'id');
    }


    public function getByRevision($rid){
        return $this->repository->createQueryBuilder('m')
            ->leftJoin('m.articles', 'r')
            ->where('r.id = ?1')
            ->setParameter(1, $rid)
            ->getQuery()->getResult();
    }


    public function addMedia($title, $type, Nette\Http\FileUpload $file){
        if(!$file->isOk()){
            return false;
        }

        $fileName = $file->getSanitizedName();
        $dir =  __DIR__ . "/../../www/" . $this->targetPath;
        $file->move($dir . "/" . $fileName);

        $dbPath = $this->targetPath . "/" . $fileName;

        $this->em->beginTransaction();
        try{
            $media = new Media();
            $media->title = $title;
            $media->type = $type;
            $media->path = $dbPath;
            $media->created = new \DateTime();

            $this->em->persist($media);
            $this->em->flush();
            $this->em->commit();
            return true;
        } catch(Exception $e){
            $this->em->rollBack();
            $this->em->close();
            return false;
        }
    }

    public function addRevisionMedia($revision, $media){
        $rev = $this->em->getRepository(Revision::class)->find($revision);
        $med = $this->repository->find($media);
        $rev->addMedia($med);
        $this->em->flush();
    }

}
