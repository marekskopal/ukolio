<?php

declare(strict_types=1);

namespace Migrations;

use MarekSkopal\ORM\Enum\Type;
use MarekSkopal\ORM\Migrations\Migration\Migration;

final class EmailVerificationMigration extends Migration
{
	public function up(): void
	{
		$this->table('users')
			->addColumn('email_verified', Type::Boolean, default: false)
			->alter();

		$this->table('email_verification_tokens')
			->addColumn('id', Type::Int, autoincrement: true, primary: true)
			->addColumn('user_id', Type::Int, size: 11)
			->addColumn('token_hash', Type::String, size: 64)
			->addColumn('expires_at', Type::Timestamp)
			->addColumn('used_at', Type::Timestamp, nullable: true)
			->addColumn('created_at', Type::Timestamp)
			->addColumn('updated_at', Type::Timestamp)
			->addIndex(['user_id'], 'email_verification_tokens_user_id_index', false)
			->addIndex(['token_hash'], 'email_verification_tokens_token_hash_unique', true)
			->addForeignKey('user_id', 'users', 'id', 'email_verification_tokens_user_id_fk')
			->create();

		$pdo = $this->databaseProvider->getDatabase()->getPdo();
		$pdo->exec('UPDATE users SET email_verified = TRUE');
	}

	public function down(): void
	{
		$this->table('email_verification_tokens')->drop();

		$this->table('users')
			->dropColumn('email_verified')
			->alter();
	}
}
