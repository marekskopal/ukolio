<?php

declare(strict_types=1);

namespace Migrations;

use MarekSkopal\ORM\Migrations\Migration\Migration;

final class AgentAttributionMigration extends Migration
{
	public function up(): void
	{
		$pdo = $this->databaseProvider->getDatabase()->getPdo();

		$pdo->exec('ALTER TABLE tasks ADD COLUMN created_by_agent TINYINT(1) NOT NULL DEFAULT 0');
		$pdo->exec('ALTER TABLE tasks ADD INDEX tasks_created_by_agent_index (created_by_agent)');

		$pdo->exec("ALTER TABLE events ADD COLUMN actor_type ENUM('Human','Agent') NOT NULL DEFAULT 'Human'");
		$pdo->exec('ALTER TABLE events ADD COLUMN mcp_client_id VARCHAR(128) NULL DEFAULT NULL');
		$pdo->exec('ALTER TABLE events ADD COLUMN mcp_client_name VARCHAR(255) NULL DEFAULT NULL');
		$pdo->exec('ALTER TABLE events ADD INDEX events_actor_type_index (actor_type)');
		$pdo->exec('ALTER TABLE events ADD INDEX events_mcp_client_id_index (mcp_client_id)');
	}

	public function down(): void
	{
		$pdo = $this->databaseProvider->getDatabase()->getPdo();

		$pdo->exec('ALTER TABLE tasks DROP INDEX tasks_created_by_agent_index');
		$pdo->exec('ALTER TABLE tasks DROP COLUMN created_by_agent');

		$pdo->exec('ALTER TABLE events DROP INDEX events_actor_type_index');
		$pdo->exec('ALTER TABLE events DROP INDEX events_mcp_client_id_index');
		$pdo->exec('ALTER TABLE events DROP COLUMN actor_type');
		$pdo->exec('ALTER TABLE events DROP COLUMN mcp_client_id');
		$pdo->exec('ALTER TABLE events DROP COLUMN mcp_client_name');
	}
}
