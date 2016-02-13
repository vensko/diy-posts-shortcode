<?php
/*
Plugin Name: DIY Posts Shortcode
Plugin URI:  https://d.vensko.net/diy-posts-shortcode
Description: Create custom post lists with inline templates.
Version:     0.1
Author:      Dzmitry Vensko
Author URI:  https://d.vensko.net
License:     MIT
License URI: https://tldrlegal.com/license/mit-license
*/

class DIY_Posts_Shortcode
{
	protected static $tags = [];

	public static function register($name, callable $callable = null)
	{
		if ($callable === null) {
			if (is_array($name)) {
				if (key($name)) {
					static::$tags = array_merge(static::$tags, $name);
				} else {
					static::$tags = array_merge(static::$tags, array_combine($name, $name));
				}
			} else if (is_string($name) && function_exists($name)) {
				static::$tags[$name] = $name;
			}
		} else {
			static::$tags[$name] = $callable;
		}
	}

	public static function registered($name)
	{
		return isset(static::$tags[$name]);
	}

	public static function getRegistered()
	{
		return static::$tags;
	}

	public static function template($atts, $content = '')
	{
		global $post;

		$currentPage = $post;
		$globalPostNotIn = get_query_var('post__not_in');
		$posts = [];

		extract(shortcode_atts([
			'q' => null,
			'sep' => '',
			'loop' => null,
			'item' => null,
		], $atts));

		if ($q) {
			$q = preg_replace('~&#x0*([0-9a-f]+);~ei', 'chr(hexdec("\\1"))', $q);
			$q = preg_replace('~&#0*([0-9]+);~e', 'chr(\\1)', $q);
		}

		if (!$loop || $q !== null || $content !== '') {
			set_query_var('post__not_in', array_merge($globalPostNotIn, [$currentPage->ID]));
			$posts = get_posts($q);
		}

		if ($content !== '') {

			$output = [];

			foreach ($posts as $post) {
				$template = $content;

				$template = preg_replace_callback('/\{if (\!?)(\w+) ?([^}]*)\}(.+?)\{\/if\}/s', function ($matches) {
					if (DIY_Posts_Shortcode::registered($matches[2]) && function_exists($matches[2])) {
						$bool = $matches[3] !== '' ? call_user_func_array($matches[1], str_getcsv($matches[3], ' ')) : $matches[2]();
						if ($matches[1]) $bool = !$bool;
						return $bool ? $matches[4] : '';
					} else {
						return $matches[0];
					}
				}, $template);

				$output[] = preg_replace_callback('/\{(\w+) ?([^}]*)\}/', function ($matches) {
					if (DIY_Posts_Shortcode::registered($matches[1]) && function_exists($matches[1])) {
						return $matches[2] !== '' ? call_user_func_array($matches[1], str_getcsv($matches[2], ' ')) : $matches[1]();
					} else {
						return $matches[0];
					}
				}, $template);

			}

			$output = implode($sep, $output);

		} else {

			ob_start();

			if ($loop && locate_template($loop.'.php', false, false)) {
				get_template_part($loop);
			} else {
				$file = $item ?: 'content';
				$fileExists = locate_template($file.'.php', false, false);

				foreach ($posts as $post) {
					setup_postdata($post);

					if ($fileExists) {
						get_template_part($file);
					} else {
						echo '<p><a href="'.get_the_permalink().'">'.get_the_title().'</a></p>';
					}
				}
			}

			$output = ob_get_clean();

		}

		wp_reset_postdata();
		set_query_var('post__not_in', $globalPostNotIn);

		return $output;
	}
}

add_shortcode('posts', 'DIY_Posts_Shortcode::template');

DIY_Posts_Shortcode::register([
	'get_the_title',
	'get_the_permalink',
	'get_post_permalink',
	'get_the_content',
	'get_the_excerpt',
	'get_avatar',
	'get_the_date',
	'get_the_author',
	'get_the_author_link',
	'get_author_posts_url',
	'get_archives_link',
	'wp_get_shortlink',
	'get_post_thumbnail_id',
	'get_the_post_thumbnail',
	'has_post_thumbnail',
]);