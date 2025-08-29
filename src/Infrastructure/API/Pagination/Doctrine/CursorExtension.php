<?php

declare(strict_types=1);

namespace App\Infrastructure\API\Pagination\Doctrine;

use ApiPlatform\Doctrine\Orm\Extension\QueryCollectionExtensionInterface;
use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Metadata\Operation;
use App\Infrastructure\API\Pagination\CursorEncoder;
use App\Infrastructure\API\Pagination\InvalidCursorException;
use Doctrine\ORM\QueryBuilder;

final class CursorExtension implements QueryCollectionExtensionInterface
{
    public function __construct(private CursorEncoder $encoder)
    {
    }

    public function applyToCollection(QueryBuilder $queryBuilder, QueryNameGeneratorInterface $queryNameGenerator, string $resourceClass, Operation $operation = null, array $context = []): void
    {
        $filters = $context['filters'] ?? [];
        $alias = $queryBuilder->getRootAliases()[0] ?? 'o';

        if (isset($filters['cursor'])) {
            $cursor = $this->encoder->decode((string) $filters['cursor']);
            $queryBuilder
                ->andWhere(sprintf('(%1$s.createdAt < :cursorCreatedAt) OR (%1$s.createdAt = :cursorCreatedAt AND %1$s.id < :cursorId)', $alias))
                ->setParameter('cursorCreatedAt', $cursor->createdAt)
                ->setParameter('cursorId', $cursor->id)
                ->orderBy($alias . '.createdAt', 'DESC')
                ->addOrderBy($alias . '.id', 'DESC');
            $limit = (int) ($filters['limit'] ?? 50);
            if ($limit > 200) {
                $limit = 200;
            }
            $queryBuilder->setMaxResults($limit + 1);
            return;
        }

        if (isset($filters['limit'])) {
            $limit = (int) $filters['limit'];
            if ($limit > 0) {
                $queryBuilder->setMaxResults($limit);
            }
        }
    }
}
