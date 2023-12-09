<?php

function tiny_slider_images(): string
{
	global $txt, $context, $settings;

	return '
	<div x-data="handleImages()">
		<table class="add_option centertext table_grid">
			<tbody>
				<template x-for="(image, index) in images" :key="index">
					<tr class="sort_table windowbg">
						<td style="cursor: move">
							<table class="plugin_options table_grid">
								<tbody>
									<tr class="windowbg">
										<td style="width: 90px"><img alt="*" :src="image.link"></td>
										<td style="display: flex; flex-direction: column; gap: 10px">
											<div>
												' . $context['lp_icon_set']['arrows'] . '
												<button type="button" class="button" @click="removeImage(index)">
													<span class="main_icons delete"></span> ' . $txt['remove'] . '
												</button>
											</div>
											<input type="url" x-model="image.link" name="image_link[]" required placeholder="' . $txt['lp_tiny_slider']['link_placeholder'] . '">
										</td>
									</tr>
									<tr class="windowbg">
										<td colspan="2">
											<input type="text" x-model="image.title" name="image_title[]" maxlength="255" placeholder="' . $txt['lp_tiny_slider']['title_placeholder'] . '">
										</td>
									</tr>
								</tbody>
							</table>
						</td>
					</tr>
				</template>
			</tbody>
		</table>
		<button type="button" class="button floatnone" @click="addImage()"><span class="main_icons plus"></span> ' . $txt['lp_tiny_slider']['image_add'] . '</button>
	</div>
	<script src="' . $settings['default_theme_url'] . '/scripts/light_portal/Sortable.min.js"></script>
	<script>
		document.addEventListener("alpine:initialized", () => {
			const images = document.querySelectorAll(".sort_table");
			images.forEach(function (el) {
				Sortable.create(el, {
					group: "images",
					animation: 500,
				});
			});
		});

		function handleImages() {
			return {
				images: ' . ($context['lp_block']['options']['parameters']['images'] ?: '[]') . ',
				addImage() {
					this.images.push({
						link: "",
						title: ""
					})
				},
				removeImage(index) {
					this.images.splice(index, 1)
				}
			}
		}
	</script>';
}
