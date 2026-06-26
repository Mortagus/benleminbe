<?php

declare(strict_types=1);

namespace App\Private\Security\Repository;

use App\Entity\Private\PasskeyCredential;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use ParagonIE\ConstantTime\Base64UrlSafe;
use Psr\Clock\ClockInterface;
use Webauthn\Bundle\Repository\CanSaveCredentialRecord;
use Webauthn\Bundle\Repository\CredentialRecordRepositoryInterface;
use Webauthn\Bundle\Repository\PublicKeyCredentialSourceRepositoryInterface;
use Webauthn\CredentialRecord;
use Webauthn\PublicKeyCredentialUserEntity;

final readonly class PasskeyCredentialRepository implements PublicKeyCredentialSourceRepositoryInterface, CanSaveCredentialRecord
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private ClockInterface $clock,
    ) {
    }

    /**
     * @return list<CredentialRecord>
     */
    public function findAllForUserEntity(PublicKeyCredentialUserEntity $publicKeyCredentialUserEntity): array
    {
        $credentials = $this->getRepository()->findBy(
            ['userHandle' => $publicKeyCredentialUserEntity->id],
            ['createdAt' => 'ASC', 'id' => 'ASC'],
        );

        return array_map(
            static fn (PasskeyCredential $credential): CredentialRecord => $credential->toCredentialRecord(),
            $credentials,
        );
    }

    public function findOneByCredentialId(string $publicKeyCredentialId): ?CredentialRecord
    {
        $credential = $this->findEntityByCredentialId($publicKeyCredentialId);

        return $credential?->toCredentialRecord();
    }

    public function saveCredentialRecord(CredentialRecord $credentialRecord): void
    {
        $this->saveCredentialRecordWithLabel($credentialRecord, null);
    }

    public function saveCredentialRecordWithLabel(
        CredentialRecord $credentialRecord,
        ?string $displayName,
    ): PasskeyCredential
    {
        $entity = $this->findEntityByCredentialId($credentialRecord->publicKeyCredentialId);
        $now = $this->clock->now();

        if ($entity === null) {
            $displayName ??= PasskeyCredential::DEFAULT_LABEL;
            $entity = PasskeyCredential::fromCredentialRecord($credentialRecord, $displayName, $now);
            $this->entityManager->persist($entity);
            $this->entityManager->flush();

            return $entity;
        }

        $entity->updateFromCredentialRecord($credentialRecord, DateTimeImmutable::createFromInterface($now));
        if ($displayName !== null) {
            $entity->rename($displayName);
        }
        $this->entityManager->flush();

        return $entity;
    }

    /**
     * @return list<PasskeyCredential>
     */
    public function findAllForCurrentUser(string $userHandle): array
    {
        return $this->getRepository()->findBy(
            ['userHandle' => $userHandle],
            ['createdAt' => 'ASC', 'id' => 'ASC'],
        );
    }

    public function findOneById(int $id): ?PasskeyCredential
    {
        return $this->getRepository()->find($id);
    }

    public function countForUserHandle(string $userHandle): int
    {
        return (int) $this->getRepository()->createQueryBuilder('credential')
            ->select('COUNT(credential.id)')
            ->andWhere('credential.userHandle = :userHandle')
            ->setParameter('userHandle', $userHandle)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function delete(PasskeyCredential $credential): void
    {
        $this->entityManager->remove($credential);
        $this->entityManager->flush();
    }

    public function rename(PasskeyCredential $credential, string $displayName): void
    {
        $credential->rename($displayName);
        $this->entityManager->flush();
    }

    private function findEntityByCredentialId(string $publicKeyCredentialId): ?PasskeyCredential
    {
        return $this->getRepository()->findOneBy([
            'publicKeyCredentialId' => Base64UrlSafe::encodeUnpadded($publicKeyCredentialId),
        ]);
    }

    private function getRepository(): EntityRepository
    {
        return $this->entityManager->getRepository(PasskeyCredential::class);
    }
}
