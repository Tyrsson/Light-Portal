<?php

/**
 * The output of blocks BEFORE content
 *
 * Вывод блоков ДО контента
 *
 * @return void
 */
function template_portal_above()
{
	global $modSettings, $context;

	echo '
	<div id="lp_layout"', !empty($modSettings['lp_swap_header_footer']) ? ' class="row reverse2"' : '', '>';

	// Header | Шапка
	if (!empty($context['lp_blocks']['header'])) {
		echo '
		<div class="row center-xs">
			<div class="col-xs-', $context['lp_header_panel_width'], '">';

		lp_show_blocks('header');

		echo '
			</div>
		</div>';
	}

	echo '
		<div class="row', !empty($modSettings['lp_swap_left_right']) ? ' reverse' : '', '">';

	// Left Side | Левая панель
	if (!empty($context['lp_blocks']['left'])) {
		echo '
			<div class="col-xs-12 col-sm-12 col-md-', $context['lp_left_panel_width']['md'], ' col-lg-', $context['lp_left_panel_width']['lg'], ' col-xl-', $context['lp_left_panel_width']['xl'], '">';

		lp_show_blocks('left');

		echo '
			</div>';
	}

	$md = 12 - ($context['lp_left_panel_width']['md'] + $context['lp_right_panel_width']['md']);
	$lg = 12 - ($context['lp_left_panel_width']['lg'] + $context['lp_right_panel_width']['lg']);
	$xl = 12 - ($context['lp_left_panel_width']['xl'] + $context['lp_right_panel_width']['xl']);

	echo '
			<div class="col-xs', !empty($context['lp_blocks']['left']) && !empty($context['lp_blocks']['right']) ? ('-12 col-sm-12 col-md-' . $md . ' col-lg-' . $lg . ' col-xl-' . $xl) : '', '">
				<div', !empty($modSettings['lp_swap_top_bottom']) ? ' class="row reverse2"' : '', '>';

	// Center (top) | Центр (верх)
	if (!empty($context['lp_blocks']['top'])) {
		echo '

				<div class="row">
					<div class="col-xs">';

		lp_show_blocks('top');

		echo '
					</div>
				</div>';
	}

	echo '
				<div class="row">
					<div class="col-xs noup">
						<main>';
}

/**
 * The output of blocks AFTER content
 *
 * Вывод блоков ПОСЛЕ контента
 *
 * @return void
 */
function template_portal_below()
{
	global $context;

	echo '
						</main>
					</div>
				</div>';

	// Center (bottom) | Центр (низ)
	if (!empty($context['lp_blocks']['bottom'])) {
		echo '
				<div class="row">
					<div class="col-xs">';

		lp_show_blocks('bottom');

		echo '
					</div>
				</div>';
	}

	echo '
				</div>
			</div>';

	// Right Side | Правая панель
	if (!empty($context['lp_blocks']['right'])) {
		echo '
			<div class="col-xs-12 col-sm-12 col-md-', $context['lp_right_panel_width']['md'], ' col-lg-', $context['lp_right_panel_width']['lg'], ' col-xl-', $context['lp_right_panel_width']['xl'], '">';

		lp_show_blocks('right');

		echo '
			</div>';
	}

	echo '
		</div>';

	// Footer | Подвал
	if (!empty($context['lp_blocks']['footer'])) {
		echo '
		<div class="row center-xs">
			<div class="col-xs-', $context['lp_footer_panel_width'], '">';

		lp_show_blocks('footer');

		echo '
			</div>
		</div>';
	}

	echo '
	</div>';
}

/**
 * Output all blocks by placement
 *
 * Вывод всех блоков по размещению
 *
 * @param string $placement
 * @return void
 */
function lp_show_blocks($placement = '')
{
	global $context, $scripturl;

	if (empty($placement) || empty($context['lp_blocks'][$placement]))
		return;

	if (!empty($context['lp_panel_direction'][$placement])) {
		echo '
		<div class="row">';
	}

	foreach ($context['lp_blocks'][$placement] as $id => $block) {
		echo '
			<aside id="block_', $block['id'], '" class="block_', $block['type'], !empty($context['lp_panel_direction'][$placement]) ? ' col-xs' : '', '">';

		if (!empty($block['title_style']))
			$block['title'] = '<span style="' . $block['title_style'] . '">' . $block['title'] . '</span>';

		if ($context['allow_light_portal_manage_blocks'] && !empty($block['title']))
			$block['title'] = '<a href="' . $scripturl . '?action=admin;area=lp_blocks;sa=edit;id=' . $block['id'] . '">' . $block['title'] . '</a>';

		if (!empty($block['title_class']))
			echo sprintf($context['lp_all_title_classes'][$block['title_class']], $block['title']);
		else
			echo $block['title'];

		if (empty($block['title_class']))
			$block['content_style'] = 'border-radius: 7px;' . $block['content_style'];

		$style = '';
		if (!empty($block['content_style']))
			$style = ' style="' . $block['content_style'] . '"';

		if (!empty($block['content_class']))
			echo sprintf($context['lp_all_content_classes'][$block['content_class']], $block['content'], $style);
		else
			echo $block['content'];

		echo '
			</aside>';
	}

	if (!empty($context['lp_panel_direction'][$placement])) {
		echo '
		</div>';
	}
}
