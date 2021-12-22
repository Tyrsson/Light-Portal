<?php

declare(strict_types = 1);

/**
 * Prune.php
 *
 * @package Light Portal
 * @link https://dragomano.ru/mods/light-portal
 * @author Bugo <bugo@dragomano.ru>
 * @copyright 2019-2022 Bugo
 * @license https://spdx.org/licenses/GPL-3.0-or-later.html GPL-3.0-or-later
 *
 * @version 2.0
 */

namespace Bugo\LightPortal\Tasks;

final class Prune extends \SMF_BackgroundTask
{
	public function execute(): bool
	{
		global $smcFunc;

		@ini_set('opcache.enable', '0');

		$this->removeRedundantValues();
		$this->updateNumComments();
		$this->optimizeTables();

		$smcFunc['db_insert']('insert',
			'{db_prefix}background_tasks',
			array(
				'task_file'    => 'string-255',
				'task_class'   => 'string-255',
				'task_data'    => 'string',
				'claimed_time' => 'int'
			),
			array(
				'$sourcedir/LightPortal/tasks/Prune.php',
				__CLASS__,
				'',
				time() + (7 * 24 * 60 * 60)
			),
			array('id_task')
		);

		return true;
	}

	private function removeRedundantValues()
	{
		global $smcFunc;

		$smcFunc['db_query']('', '
			DELETE FROM {db_prefix}lp_params
			WHERE value = {string:empty_value}',
			array(
				'empty_value' => ''
			)
		);

		$select_value = $smcFunc['db_title'] === POSTGRE_TITLE ? "string_agg(value, ',')" : 'GROUP_CONCAT(value)';

		$request = $smcFunc['db_query']('', '
			SELECT ' . $select_value . ' AS value
			FROM {db_prefix}lp_params
			WHERE type = {literal:page}
				AND name = {literal:keywords}',
			array()
		);

		[$usedTags] = $smcFunc['db_fetch_row']($request);
		$smcFunc['db_free_result']($request);

		if (! empty($usedTags)) {
			$smcFunc['db_query']('', '
				DELETE FROM {db_prefix}lp_tags
				WHERE tag_id NOT IN ({array_int:tags})',
				array(
					'tags' => explode(',', $usedTags)
				)
			);
		}

		$smcFunc['db_query']('', '
			DELETE FROM {db_prefix}lp_titles
			WHERE title = {string:empty_value}',
			array(
				'empty_value' => ''
			)
		);

		$smcFunc['db_query']('', '
			DELETE FROM {db_prefix}lp_comments
			WHERE parent_id <> 0
				AND parent_id NOT IN (SELECT id FROM {db_prefix}lp_comments)',
			array()
		);
	}

	private function updateNumComments()
	{
		global $smcFunc;

		$request = $smcFunc['db_query']('', '
			SELECT p.page_id, COUNT(c.id) AS amount
			FROM {db_prefix}lp_pages p
				LEFT JOIN {db_prefix}lp_comments c ON (c.page_id = p.page_id AND p.status = {int:status})
			GROUP BY p.page_id
			ORDER BY p.page_id',
			array(
				'status' => 1
			)
		);

		$pages = [];
		while ($row = $smcFunc['db_fetch_assoc']($request))
			$pages[$row['page_id']] = $row['amount'];

		$smcFunc['db_free_result']($request);

		if (empty($pages))
			return;

		$line = '';
		foreach ($pages as $page_id => $num_comments)
			$line .= ' WHEN page_id = ' . $page_id . ' THEN ' . $num_comments;

		$smcFunc['db_query']('', '
			UPDATE {db_prefix}lp_pages
			SET num_comments = CASE ' . $line . '
				ELSE num_comments
				END
			WHERE page_id IN ({array_int:pages})',
			array(
				'pages' => array_keys($pages)
			)
		);
	}

	private function optimizeTables()
	{
		global $smcFunc;

		$tables = [
			'lp_blocks',
			'lp_categories',
			'lp_comments',
			'lp_pages',
			'lp_params',
			'lp_tags',
			'lp_titles'
		];

		db_extend();

		foreach ($tables as $table)
			$smcFunc['db_optimize_table']('{db_prefix}' . $table);
	}
}
