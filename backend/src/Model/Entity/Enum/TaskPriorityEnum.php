<?php

declare(strict_types=1);

namespace Ukolio\Model\Entity\Enum;

enum TaskPriorityEnum: string
{
	case Low = 'Low';
	case Medium = 'Medium';
	case High = 'High';
}
