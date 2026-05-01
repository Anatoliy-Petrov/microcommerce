<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\RefreshToken;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<RefreshToken>
 */
class RefreshTokenRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, RefreshToken::class);
    }

    public function findByTokenHash(string $hash): ?RefreshToken
    {
        return $this->findOneBy(['tokenHash' => $hash]);
    }

    public function save(RefreshToken $token, bool $flush = false): void
    {
        $this->getEntityManager()->persist($token);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function revokeAllForUser(string $userId): void
    {
        $this->createQueryBuilder('rt')
            ->update()
            ->set('rt.revokedAt', ':now')
            ->where('rt.user = :userId AND rt.revokedAt IS NULL')
            ->setParameter('userId', $userId)
            ->setParameter('now', new \DateTimeImmutable())
            ->getQuery()
            ->execute();
    }
}