<?php declare(strict_types=1);

/**
 * TitleList.php
 *
 * @package Light Portal
 * @link https://dragomano.ru/mods/light-portal
 * @author Bugo <bugo@dragomano.ru>
 * @copyright 2019-2024 Bugo
 * @license https://spdx.org/licenses/GPL-3.0-or-later.html GPL-3.0-or-later
 *
 * @version 2.5
 */

namespace Bugo\LightPortal\Lists;

use Bugo\LightPortal\Helper;
use Bugo\LightPortal\Utils\Utils;

if (! defined('SMF'))
	die('No direct access...');

final class TitleList implements ListInterface
{
	use Helper;

	public function getAll(): array
	{
		$result = Utils::$smcFunc['db_query']('', '
			SELECT item_id, lang, title
			FROM {db_prefix}lp_titles
			WHERE type = {string:type}
				AND title <> {string:blank_string}
			ORDER BY lang, title',
			[
				'type'         => 'page',
				'blank_string' => '',
			]
		);

		$titles = [];
		while ($row = Utils::$smcFunc['db_fetch_assoc']($result)) {
			$titles[$row['item_id']][$row['lang']] = $row['title'];
		}

		Utils::$smcFunc['db_free_result']($result);
		Utils::$context['lp_num_queries']++;

		return $titles;
	}
}
