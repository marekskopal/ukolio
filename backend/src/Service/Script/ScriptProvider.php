<?php

declare(strict_types=1);

namespace Ukolio\Service\Script;

use DateTimeImmutable;
use Iterator;
use RuntimeException;
use Ukolio\Model\Entity\Enum\ScriptTriggerEnum;
use Ukolio\Model\Entity\Script;
use Ukolio\Model\Entity\ScriptRun;
use Ukolio\Model\Entity\User;
use Ukolio\Model\Entity\Workspace;
use Ukolio\Model\Repository\ScriptRepository;
use Ukolio\Model\Repository\ScriptRunRepository;

final readonly class ScriptProvider implements ScriptProviderInterface
{
	private const int MaxNameLength = 120;
	private const int MaxSourceLength = 102400;
	private const int MaxScriptsPerWorkspace = 50;
	private const int MaxScheduledPerWorkspace = 20;

	public function __construct(private ScriptRepository $scriptRepository, private ScriptRunRepository $scriptRunRepository,)
	{
	}

	/** @return Iterator<Script> */
	public function listForWorkspace(Workspace $workspace): Iterator
	{
		return $this->scriptRepository->findByWorkspace($workspace->id);
	}

	public function get(Workspace $workspace, int $scriptId): ?Script
	{
		return $this->scriptRepository->findOneByWorkspaceAndId($workspace->id, $scriptId);
	}

	public function create(
		User $author,
		Workspace $workspace,
		string $name,
		string $source,
		ScriptTriggerEnum $trigger,
		?string $triggerConfig,
		bool $active,
	): Script {
		$name = $this->validateName($workspace, $name, null);
		$this->validateSource($source);
		$triggerConfig = $this->validateTriggerConfig($trigger, $triggerConfig);

		if ($this->scriptRepository->countByWorkspace($workspace->id) >= self::MaxScriptsPerWorkspace) {
			throw new RuntimeException(sprintf('Workspace script limit reached (max %d).', self::MaxScriptsPerWorkspace));
		}
		$this->assertScheduledCapacity($workspace, $trigger, null);

		$now = new DateTimeImmutable();
		$script = new Script(
			workspace: $workspace,
			createdBy: $author,
			name: $name,
			source: $source,
			trigger: $trigger,
			triggerConfig: $triggerConfig,
			active: $active,
		);
		$script->createdAt = $now;
		$script->updatedAt = $now;
		$this->scriptRepository->persist($script);

		return $script;
	}

	public function update(
		Script $script,
		string $name,
		string $source,
		ScriptTriggerEnum $trigger,
		?string $triggerConfig,
		bool $active,
	): Script {
		$script->name = $this->validateName($script->workspace, $name, $script->id);
		$this->validateSource($source);
		$this->assertScheduledCapacity($script->workspace, $trigger, $script->id);

		$script->source = $source;
		$script->trigger = $trigger;
		$script->triggerConfig = $this->validateTriggerConfig($trigger, $triggerConfig);
		$script->active = $active;
		$script->updatedAt = new DateTimeImmutable();
		$this->scriptRepository->persist($script);

		return $script;
	}

	public function delete(Script $script): void
	{
		$this->scriptRepository->delete($script);
	}

	/** @return Iterator<ScriptRun> */
	public function runHistory(Script $script, int $limit, int $offset): Iterator
	{
		return $this->scriptRunRepository->findByScript($script->id, $limit, $offset);
	}

	private function validateName(Workspace $workspace, string $name, ?int $ignoreId): string
	{
		$name = trim($name);
		if ($name === '' || mb_strlen($name) > self::MaxNameLength) {
			throw new RuntimeException(sprintf('Script name must be 1-%d characters.', self::MaxNameLength));
		}

		$existing = $this->scriptRepository->findOneByWorkspaceAndName($workspace->id, $name);
		if ($existing !== null && $existing->id !== $ignoreId) {
			throw new RuntimeException(sprintf('A script named "%s" already exists in this workspace.', $name));
		}

		return $name;
	}

	private function validateSource(string $source): void
	{
		if (strlen($source) > self::MaxSourceLength) {
			throw new RuntimeException(sprintf('Script source must be at most %d bytes.', self::MaxSourceLength));
		}
	}

	private function validateTriggerConfig(ScriptTriggerEnum $trigger, ?string $triggerConfig): ?string
	{
		$triggerConfig = $triggerConfig !== null ? trim($triggerConfig) : null;

		if ($trigger === ScriptTriggerEnum::Manual) {
			return null;
		}

		if ($triggerConfig === null || $triggerConfig === '') {
			throw new RuntimeException(sprintf('Trigger "%s" requires a configuration (cron expression or event types).', $trigger->value));
		}

		return $triggerConfig;
	}

	private function assertScheduledCapacity(Workspace $workspace, ScriptTriggerEnum $trigger, ?int $ignoreId): void
	{
		if ($trigger !== ScriptTriggerEnum::Scheduled) {
			return;
		}

		$count = $this->scriptRepository->countByWorkspaceAndTrigger($workspace->id, ScriptTriggerEnum::Scheduled);
		$existing = $ignoreId !== null ? $this->scriptRepository->findOneByWorkspaceAndId($workspace->id, $ignoreId) : null;
		// Subtract the script being updated if it is already a scheduled one (it occupies a slot already).
		if ($existing !== null && $existing->trigger === ScriptTriggerEnum::Scheduled) {
			$count--;
		}

		if ($count >= self::MaxScheduledPerWorkspace) {
			throw new RuntimeException(sprintf('Workspace scheduled-script limit reached (max %d).', self::MaxScheduledPerWorkspace));
		}
	}
}
