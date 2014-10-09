<?php
/**
 * PHP v5.3 Handlers
 *
 * @since 141004 First documented version.
 * @copyright WebSharks, Inc. <http://www.websharks-inc.com>
 * @license GNU General Public License, version 2
 */
if(!function_exists('wp_php53'))
{
	/**
	 * This server is running PHP v5.3+?
	 *
	 * @return boolean `TRUE` if running PHP v5.3+; `FALSE` otherwise.
	 */
	function wp_php53() // Called automatically on `include()` or `require()`.
	{
		return version_compare(PHP_VERSION, $GLOBALS['wp_php_rv'], '>=');
	}
}
if(!function_exists('wp_php53_notice'))
{
	/**
	 * Creates a WP Dashboard notice regarding PHP requirements.
	 *
	 * @param string $software_name Optional. Name of the calling theme/plugin. Defaults to `ucwords([calling file basedir])`.
	 * @param string $software_text_domain Optional i18n text domain. Defaults to slugified `$software_name`.
	 * @param string $notice_cap Optional. Capability to view notice. Defaults to `activate_plugins`.
	 * @param string $notice_action Optional. Action hook. Defaults to `all_admin_notices`.
	 * @param string $notice Optional. Custom notice HTML instead of default markup.
	 */
	function wp_php53_notice($software_name = '', $software_text_domain = '', $notice_cap = '', $notice_action = '', $notice = '')
	{
		$software_name        = trim((string)$software_name);
		$software_text_domain = trim((string)$software_text_domain);
		$notice_cap           = trim((string)$notice_cap);
		$notice_action        = trim((string)$notice_action);
		$notice               = trim((string)$notice);

		if(!$notice_cap) // Use default cap?
			$notice_cap = 'activate_plugins';

		if(!$notice_action) // Use default action?
			$notice_action = 'all_admin_notices';

		if(!$software_name) // Use default generic name?
		{
			$software_name = 'This Software'; // Default generic value.
			// Let's try to do better! We can use the basedir of the calling file.
			if(($_debug_backtrace = @debug_backtrace()) && !empty($_debug_backtrace[0]['file']))
				if(($_calling_file_basedir = strtolower(basename(dirname($_debug_backtrace[0]['file'])))))
					$software_name = ucwords(trim(preg_replace('/[^a-z0-9]+/i', ' ', $_calling_file_basedir)));
			unset($_debug_backtrace, $_calling_file_basedir); // Housekeeping.
		}
		if(!$software_text_domain) // Use default text domain?
			$software_text_domain = trim(preg_replace('/[^a-z0-9\-]/i', '-', strtolower($software_name)), '-');

		if(!$notice) // Use the default notice? This will amost always suffice.
		{
			$notice = '<a href="http://php.net/" target="_blank" title="PHP.net">'.
			          '<img src="//cdn.websharks-inc.com/media/images/php-icon.png" style="width:60px; float:left; margin:0 10px 0 0;" alt="PHP" />'.
			          '</a>'; // PHP icon served from the WebSharks™ CDN. Supports both `http://` and `https://`.

			$notice .= ' '.sprintf(__('<strong>%1$s is NOT active.</strong>', $software_text_domain), esc_html($software_name));
			$notice .= ' '.sprintf(__('<strong>It requires PHP v%1$s+.</strong>', $software_text_domain), esc_html($GLOBALS['wp_php_rv'])).'<br />';
			$notice .= ' '.sprintf(__('&#8627; You\'re currently running an older copy of PHP v%1$s.', $software_text_domain), esc_html(PHP_VERSION)).'<br />';
			$notice .= ' '.__('<em>A simple update is necessary. Please ask your hosting company to help resolve this quickly.</em>', $software_text_domain).'<br />';
			$notice .= ' '.sprintf(__('<em>To remove this message, please upgrade. Or, remove %1$s from WordPress.</em>', $software_text_domain), esc_html($software_name));
		}
		add_action($notice_action, create_function('', 'if(!current_user_can(\''.str_replace("'", "\\'", $notice_cap).'\'))'.
		                                               '   return;'."\n". // User missing capability.

		                                               'echo \''. // Wrap `$notice` inside a WordPress error.

		                                               '<div class="error">'.
		                                               '   <p>'.
		                                               '      '.str_replace("'", "\\'", $notice).
		                                               '   </p>'.
		                                               '</div>'.

		                                               '\';'));
	}
}
if(!function_exists('wp_php53_custom_notice'))
{
	/**
	 * Creates a WP Dashboard notice regarding PHP requirements.
	 *
	 * @param string $notice Optional. Custom notice HTML instead of default markup.
	 * @param string $notice_cap Optional. Capability to view notice. Defaults to `activate_plugins`.
	 * @param string $notice_action Optional. Action hook. Defaults to `all_admin_notices`.
	 */
	function wp_php53_custom_notice($notice = '', $notice_cap = '', $notice_action = '')
	{
		wp_php53_notice('', '', $notice_cap, $notice_action, $notice);
	}
}
/**
 * Required PHP version.
 *
 * @var string Required PHP version.
 */
$GLOBALS['wp_php_rv'] = '5.3'; // Hard-coded.
/*
 * @return boolean on `include()` or `require()`.
 */
return wp_php53(); // `TRUE` if running PHP v5.3+; `FALSE` otherwise.