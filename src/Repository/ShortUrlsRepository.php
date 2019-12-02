<?php

namespace App\Repository;

use App\Entity\ShortUrls;
use DateTime;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;

/**
 * @method ShortUrls|null find($id, $lockMode = null, $lockVersion = null)
 * @method ShortUrls|null findOneBy(array $criteria, array $orderBy = null)
 * @method ShortUrls[]    findAll()
 * @method ShortUrls[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ShortUrlsRepository extends ServiceEntityRepository
{
    private $em;

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ShortUrls::class);
        
        $this->em = $this->getEntityManager();
    }

    public function getUrlList()
    {
        $qb = $this->createQueryBuilder('u');
        $qb->select('u.id', 'u.long_url', 'u.short_code');
        $query = $qb->getQuery();

        return $query->getArrayResult();
    }

    public function insertRecordToDb($url, $code)
    {
        try {
            $shortUrl = new ShortUrls();
            $shortUrl->setLongUrl($url);
            $shortUrl->setShortCode($code);
            $shortUrl->setDateCreated(new \DateTime('now'));
            $shortUrl->setCounter(0);
            $this->em->persist($shortUrl);
            $this->em->flush();

            return $shortUrl; 
        } catch (\Exception $e) {
            throw new \Exception($e->getMessage());
        }
    }

    public function insertShortCodeToDb($shortUrlObj, $shortCode)
    {
        $shortUrlObj->setShortCode($shortCode);
        $this->em->flush();

        return $shortUrlObj->getId();
    }
}
