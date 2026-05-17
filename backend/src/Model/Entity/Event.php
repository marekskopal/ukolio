<?php

declare(strict_types=1);

namespace Ukolio\Model\Entity;

use MarekSkopal\ORM\Attribute\Column;
use MarekSkopal\ORM\Attribute\ColumnEnum;
use MarekSkopal\ORM\Attribute\Entity;
use MarekSkopal\ORM\Attribute\ManyToOne;
use MarekSkopal\ORM\Enum\Type;
use Ukolio\Model\Entity\Enum\EventTypeEnum;
use Ukolio\Model\Repository\EventRepository;

#[Entity(repositoryClass: EventRepository::class)]
class Event extends AEntity
{
	public function __construct(
		#[ManyToOne(entityClass: User::class)]
		public readonly User $author,
		#[ManyToOne(entityClass: Project::class)]
		public readonly Project $project,
		#[ColumnEnum(enum: EventTypeEnum::class)]
		public EventTypeEnum $type,
		#[Column(type: Type::Text)]
		public string $metadata,
		#[Column(type: Type::Int, nullable: true)]
		public ?int $taskId = null,
	) {
	}
}
