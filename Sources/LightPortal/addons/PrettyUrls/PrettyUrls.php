<?php

namespace Bugo\LightPortal\Addons\PrettyUrls;

/**
 * PrettyUrls
 *
 * @package Light Portal
 * @link https://dragomano.ru/mods/light-portal
 * @author Bugo <bugo@dragomano.ru>
 * @copyright 2019-2020 Bugo
 * @license https://spdx.org/licenses/GPL-3.0-or-later.html GPL-3.0-or-later
 *
 * @version 1.3
 */

if (!defined('SMF'))
	die('Hacking attempt...');

class PrettyUrls
{
	/**
	 * Specifying the addon type (if 'block', you do not need to specify it)
	 *
	 * Указываем тип аддона (если 'block', то можно не указывать)
	 *
	 * @var string
	 */
	public $addon_type = 'other';

	/**
	 * Give a hint about action=portal to PrettyUrls mod
	 *
	 * Подсказываем PrettyUrls про action=portal
	 *
	 * @return void
	 */
	public function init()
	{
		global $context;

		if (!empty($context['pretty']['action_array']) && !in_array('portal', array_values($context['pretty']['action_array'])))
			$context['pretty']['action_array'][] = 'portal';
	}
}
