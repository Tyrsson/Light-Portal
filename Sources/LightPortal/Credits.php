<?php

namespace Bugo\LightPortal;

/**
 * Credits.php
 *
 * @package Light Portal
 * @link https://dragomano.ru/mods/light-portal
 * @author Bugo <bugo@dragomano.ru>
 * @copyright 2019-2020 Bugo
 * @license https://opensource.org/licenses/BSD-3-Clause BSD
 *
 * @version 1.0
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
	public static function show()
	{
		global $context, $txt;

		$context['credits_modifications'][] = self::getAuthorLink();

		if (!empty($_REQUEST['sa']) && $_REQUEST['sa'] == 'light_portal') {
			self::getComponentList();

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
	public static function getAuthorLink()
	{
		global $scripturl;

		return '<a href="https://dragomano.ru/mods/light-portal" target="_blank" rel="noopener">' . LP_NAME . '</a> | &copy; <a href="' . $scripturl . '?action=credits;sa=light_portal">2019&ndash;2020</a>, Bugo | Licensed under the <a href="https://github.com/dragomano/Light-Portal/blob/master/LICENSE" target="_blank" rel="noopener">BSD 3-Clause</a> License';
	}

	/**
	 * Collect information about used components
	 *
	 * Формируем информацию об используемых компонентах
	 *
	 * @return void
	 */
	public static function getComponentList()
	{
		global $context;

		$links = [];

		$links[] = array(
			'title' => 'Flexbox Grid',
			'link' => 'https://github.com/kristoferjoseph/flexboxgrid',
			'author' => '2013 Kristofer Joseph',
			'license' => array(
				'name' => 'the Apache License',
				'link' => 'https://github.com/kristoferjoseph/flexboxgrid/blob/master/LICENSE'
			)
		);
		$links[] = array(
			'title' => 'Font Awesome Free',
			'link' => 'https://fontawesome.com/cheatsheet/free',
			'license' => array(
				'name' => 'the Font Awesome Free License',
				'link' => 'https://github.com/FortAwesome/Font-Awesome/blob/master/LICENSE.txt'
			)
		);
		$links[] = array(
			'title' => 'Sortable.js',
			'link' => 'https://github.com/SortableJS/Sortable',
			'license' => array(
				'name' => 'the MIT License (MIT)',
				'link' => 'https://github.com/SortableJS/Sortable/blob/master/LICENSE'
			)
		);
		$links[] = array(
			'title' => 'jquery.matchHeight.js',
			'link' => 'https://github.com/liabru/jquery-match-height',
			'author' => '2015 Liam Brummitt',
			'license' => array(
				'name' => 'the MIT License (MIT)',
				'link' => 'https://github.com/liabru/jquery-match-height/blob/master/LICENSE'
			)
		);

		// Adding copyrights of used plugins
		// Возможность добавить копирайты используемых плагинов
		Subs::runAddons('credits', array(&$links));

		$context['lp_components'] = $links;
	}
}
