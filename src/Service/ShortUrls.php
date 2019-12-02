<?php

namespace App\Service;

use App\Entity\ShortUrls as ShortUrlEntity;
use Doctrine\ORM\EntityManagerInterface;

class ShortUrls
{
    private $em;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    public function getUrlList()
    {
        return $this->em->getRepository(ShortUrlEntity::class)->getUrlList();
    }

    public function urlExistsInDb($url)
    {
        return $this->em->getRepository(ShortUrlEntity::class)->findOneBy(array('long_url' => $url));
    }

    public function createShortCode($url)
    {
        $shortCode = $this->generateUniqueCode();
        $shortUrlObj = $this->em->getRepository(ShortUrlEntity::class)->insertRecordToDb($url, $shortCode);

        return $shortCode;
    }

    protected function generateUniqueCode() {
        $shortCode = substr(md5(uniqid(rand(), true)), 0, 6); // creates a 6 digit unique short id
        $exist = $this->getUrlObj($shortCode);
        if($exist) {
            $this->generateUniqueCode();
        } else {
            return $shortCode;
        }
    }

    public function getUrlObj($shortCode)
    {
        return $this->em->getRepository(ShortUrlEntity::class)->findOneBy(array('short_code' => $shortCode));
    }
}