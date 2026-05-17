<?php

declare(strict_types=1);

namespace Migrations;

use MarekSkopal\ORM\Enum\Type;
use MarekSkopal\ORM\Migrations\Migration\Migration;

final class CustomFieldsMigration extends Migration
{
	public function up(): void
	{
		$this->table('fields')
			->addColumn('id', Type::Int, autoincrement: true, primary: true)
			->addColumn('workspace_id', Type::Int, size: 11)
			->addColumn('name', Type::String)
			->addColumn('type', Type::Enum, enum: ['Text', 'Textarea', 'Select', 'Version'])
			->addColumn('required', Type::Boolean)
			->addColumn('default_value', Type::Text, nullable: true)
			->addColumn('options', Type::Text, nullable: true)
			->addColumn('created_at', Type::Timestamp)
			->addColumn('updated_at', Type::Timestamp)
			->addIndex(['workspace_id'], 'fields_workspace_id_index', false)
			->addIndex(['workspace_id', 'name'], 'fields_workspace_name_unique', true)
			->addForeignKey('workspace_id', 'workspaces', 'id', 'fields_workspace_id_fk')
			->create();

		$this->table('project_fields')
			->addColumn('id', Type::Int, autoincrement: true, primary: true)
			->addColumn('project_id', Type::Int, size: 11)
			->addColumn('field_id', Type::Int, size: 11)
			->addColumn('position', Type::Int)
			->addColumn('created_at', Type::Timestamp)
			->addColumn('updated_at', Type::Timestamp)
			->addIndex(['project_id'], 'project_fields_project_id_index', false)
			->addIndex(['project_id', 'field_id'], 'project_fields_unique', true)
			->addForeignKey('project_id', 'projects', 'id', 'project_fields_project_id_fk')
			->addForeignKey('field_id', 'fields', 'id', 'project_fields_field_id_fk')
			->create();

		$this->table('task_field_values')
			->addColumn('id', Type::Int, autoincrement: true, primary: true)
			->addColumn('task_id', Type::Int, size: 11)
			->addColumn('field_id', Type::Int, size: 11)
			->addColumn('value', Type::Text, nullable: true)
			->addColumn('created_at', Type::Timestamp)
			->addColumn('updated_at', Type::Timestamp)
			->addIndex(['task_id'], 'task_field_values_task_id_index', false)
			->addIndex(['field_id'], 'task_field_values_field_id_index', false)
			->addIndex(['task_id', 'field_id'], 'task_field_values_unique', true)
			->addForeignKey('task_id', 'tasks', 'id', 'task_field_values_task_id_fk')
			->addForeignKey('field_id', 'fields', 'id', 'task_field_values_field_id_fk')
			->create();

		$pdo = $this->databaseProvider->getDatabase()->getPdo();
		$pdo->exec(
			'ALTER TABLE events MODIFY COLUMN type ENUM('
			. "'ProjectCreated','ProjectUpdated','ProjectDeleted',"
			. "'WorkflowUpdated',"
			. "'StatusCreated','StatusUpdated','StatusDeleted','StatusMoved',"
			. "'TaskCreated','TaskUpdated','TaskDeleted','TaskMoved',"
			. "'MemberRoleChanged','OwnershipTransferred',"
			. "'AdminDeletedWorkspace','AdminDeletedUser','AdminChangedSystemRole',"
			. "'FieldCreated','FieldUpdated','FieldDeleted','ProjectFieldsUpdated'"
			. ') NOT NULL',
		);
	}

	public function down(): void
	{
		$this->table('task_field_values')->drop();
		$this->table('project_fields')->drop();
		$this->table('fields')->drop();

		$pdo = $this->databaseProvider->getDatabase()->getPdo();
		$pdo->exec(
			'ALTER TABLE events MODIFY COLUMN type ENUM('
			. "'ProjectCreated','ProjectUpdated','ProjectDeleted',"
			. "'WorkflowUpdated',"
			. "'StatusCreated','StatusUpdated','StatusDeleted','StatusMoved',"
			. "'TaskCreated','TaskUpdated','TaskDeleted','TaskMoved',"
			. "'MemberRoleChanged','OwnershipTransferred',"
			. "'AdminDeletedWorkspace','AdminDeletedUser','AdminChangedSystemRole'"
			. ') NOT NULL',
		);
	}
}
