<?php

declare(strict_types=1);

namespace TaskManager\Service\Logger;

use Psr\Log\AbstractLogger;
use Stringable;
use const JSON_PARTIAL_OUTPUT_ON_ERROR;
use const JSON_UNESCAPED_SLASHES;

final class StderrLogger extends AbstractLogger
{
	/**
	 * @param mixed $level
	 * @param array<mixed> $context
	 */
	public function log(mixed $level, string|Stringable $message, array $context = []): void
	{
		$line = sprintf('[%s] %s %s', date('Y-m-d H:i:s'), is_scalar($level) ? (string) $level : 'log', (string) $message);
		if ($context !== []) {
			$line .= ' ' . json_encode($context, JSON_UNESCAPED_SLASHES | JSON_PARTIAL_OUTPUT_ON_ERROR);
		}
		error_log($line);
	}
}
