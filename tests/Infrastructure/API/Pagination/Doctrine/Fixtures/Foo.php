<?php

declare(strict_types=1);

namespace App\Tests\Infrastructure\API\Pagination\Doctrine\Fixtures;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'foo')]
class Foo
{
    #[ORM\Id]
    #[ORM\Column(type: 'guid')]
    public string $id;

    #[ORM\Column(type: 'datetime_immutable')]
    public \DateTimeImmutable $createdAt;
}
