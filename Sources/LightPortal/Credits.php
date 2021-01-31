<?php

namespace Bugo\LightPortal;

/**
 * Credits.php
 *
 * @package Light Portal
 * @link https://dragomano.ru/mods/light-portal
 * @author Bugo <bugo@dragomano.ru>
 * @copyright 2019-2021 Bugo
 * @license https://spdx.org/licenses/GPL-3.0-or-later.html GPL-3.0-or-later
 *
 * @version 1.5
 */

if (!defined('SMF'))
	die('Hacking attempt...');

class Credits
{
	/**
	 * The mod credits for action=credits
	 *
	 * Отображаем копирайты на странице action=credits
	 *
	 * @return void
	 */
	public function show()
	{
		global $context, $txt;

		$context['credits_modifications'][] = $this->getCopyrights();

		if (Helpers::request()->filled('sa') && Helpers::request('sa') == 'light_portal') {
			$this->getComponentList();

			loadTemplate('LightPortal/ViewCredits');

			$context['sub_template']   = 'portal_credits';
			$context['robot_no_index'] = true;
			$context['page_title']     = LP_NAME . ' - ' . $txt['lp_used_components'];

			obExit();
		}
	}

	/**
	 * Return copyright information
	 *
	 * Возвращаем информацию об авторских правах
	 *
	 * @return string
	 */
	public function getCopyrights()
	{
		global $user_info, $scripturl;

		$link = $user_info['language'] == 'russian' ? 'https://dragomano.ru/mods/light-portal' : 'https://custom.simplemachines.org/mods/index.php?mod=4244';

		return '<a href="' . $link . '" target="_blank" rel="noopener" title="' . LP_VERSION . '">' . LP_NAME . '</a> | &copy; <a href="' . $scripturl . '?action=credits;sa=light_portal">2019&ndash;2021</a>, Bugo | Licensed under the <a href="https://github.com/dragomano/Light-Portal/blob/master/LICENSE" target="_blank" rel="noopener">GNU GPLv3</a> License';
	}

	/**
	 * Collect information about used components
	 *
	 * Формируем информацию об используемых компонентах
	 *
	 * @return void
	 */
	public function getComponentList()
	{
		global $context;

		isAllowedTo('light_portal_view');

		$links = array(
			array(
				'title' => 'Flexbox Grid',
				'link' => 'https://github.com/evgenyrodionov/flexboxgrid2',
				'author' => 'Kristofer Joseph',
				'license' => array(
					'name' => 'the Apache License',
					'link' => 'https://github.com/evgenyrodionov/flexboxgrid2/blob/master/LICENSE'
				)
			),
			array(
				'title' => 'Font Awesome Free',
				'link' => 'https://fontawesome.com/cheatsheet/free',
				'license' => array(
					'name' => 'the Font Awesome Free License',
					'link' => 'https://github.com/FortAwesome/Font-Awesome/blob/master/LICENSE.txt'
				)
			),
			array(
				'title' => 'Alpine.js',
				'link' => 'https://github.com/alpinejs/alpine',
				'license' => array(
					'name' => 'the MIT License',
					'link' => 'https://github.com/alpinejs/alpine/blob/master/LICENSE.md'
				)
			),
			array(
				'title' => 'Sortable.js',
				'link' => 'https://github.com/SortableJS/Sortable',
				'author' => 'All contributors to Sortable',
				'license' => array(
					'name' => 'the MIT License',
					'link' => 'https://github.com/SortableJS/Sortable/blob/master/LICENSE'
				)
			),
			array(
				'title' => 'Transliteration',
				'link' => 'https://github.com/dzcpy/transliteration',
				'license' => array(
					'name' => 'the MIT License',
					'link' => 'https://github.com/dzcpy/transliteration/blob/master/LICENSE.txt'
				)
			),
			array(
				'title' => 'Slim Select',
				'link' => 'https://slimselectjs.com',
				'author' => 'Brian Voelker',
				'license' => array(
					'name' => 'the MIT License',
					'link' => 'https://github.com/brianvoe/slim-select/blob/master/LICENSE'
				)
			)
		);

		// Adding copyrights of used plugins
		Subs::runAddons('credits', array(&$links));

		$context['lp_components'] = $links;
	}
}
