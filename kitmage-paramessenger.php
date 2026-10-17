<?php
/**
 * Plugin Name: KitMage Paramessenger
 * Plugin URI: https://kitmage.com
 * Description: Display URL-driven messages on frontend pages.
 * Version: 0.1.0
 * Author: Mike@KitMage
 * Author URI: https://kitmage.com
 * Text Domain: kitmage-paramessenger
 */

defined( 'ABSPATH' ) || exit;

/**
 * Keep message HTML to formatting and links, without scripts, styles, or embeds.
 */
function kitmage_paramessenger_sanitize_html( $html ) {
	$allowed = array(
		'p'          => array(),
		'br'         => array(),
		'strong'     => array(),
		'em'         => array(),
		'b'          => array(),
		'i'          => array(),
		'u'          => array(),
		's'          => array(),
		'ul'         => array(),
		'ol'         => array(),
		'li'         => array(),
		'blockquote' => array(),
		'code'       => array(),
		'pre'        => array(),
		'h2'         => array(),
		'h3'         => array(),
		'h4'         => array(),
		'div'        => array(),
		'span'       => array(),
		'a'          => array(
			'href'  => true,
			'title' => true,
		),
	);

	return wp_kses( $html, $allowed, array( 'http', 'https', 'mailto', 'tel' ) );
}

add_action( 'admin_menu', 'kitmage_paramessenger_register_tools_page' );

function kitmage_paramessenger_register_tools_page() {
	add_management_page(
		'KitMage Paramessenger',
		'Paramessenger',
		'manage_options',
		'kitmage-paramessenger',
		'kitmage_paramessenger_render_tools_page'
	);
}

/**
 * Compose and convert locally in wp-admin; messages are not stored.
 */
function kitmage_paramessenger_render_tools_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	$source    = '';
	$sanitized = null;
	if ( isset( $_SERVER['REQUEST_METHOD'] ) && 'POST' === $_SERVER['REQUEST_METHOD'] ) {
		check_admin_referer( 'kitmage_paramessenger_convert' );
		if ( isset( $_POST['kmp_html'] ) && is_string( $_POST['kmp_html'] ) ) {
			$source    = wp_unslash( $_POST['kmp_html'] );
			$sanitized = kitmage_paramessenger_sanitize_html( $source );
		}
	}
	?>
	<div class="wrap">
		<h1>KitMage Paramessenger</h1>
		<p>Compose a message visually or use the Text tab to enter HTML. Convert it to copyable HTML with safe formatting and links. Scripts, inline styles, classes, images, and embeds are removed. Messages are not saved.</p>
		<form method="post" action="<?php echo esc_url( admin_url( 'tools.php?page=kitmage-paramessenger' ) ); ?>">
			<?php wp_nonce_field( 'kitmage_paramessenger_convert' ); ?>
			<h2><label for="kmp_html">Message HTML</label></h2>
			<?php
			wp_editor(
				$source,
				'kmp_html',
				array(
					'textarea_name' => 'kmp_html',
					'textarea_rows' => 12,
					'media_buttons' => false,
					'teeny'         => true,
				)
			);
			submit_button( 'Convert to sanitized HTML' );
			?>
		</form>
		<?php if ( null !== $sanitized ) : ?>
			<h2><label for="kmp-result">Sanitized HTML</label></h2>
			<p>Copy this HTML into a destination that supports HTML. Destination-specific restrictions still apply.</p>
			<textarea id="kmp-result" class="large-text code" rows="10" readonly><?php echo esc_textarea( $sanitized ); ?></textarea>
			<p><button type="button" class="button" id="kmp-copy">Copy sanitized HTML</button> <span id="kmp-copy-status" role="status" aria-live="polite"></span></p>
			<script>
			(function () {
				const button = document.getElementById('kmp-copy');
				const result = document.getElementById('kmp-result');
				const status = document.getElementById('kmp-copy-status');
				button.addEventListener('click', async function () {
					try {
						await navigator.clipboard.writeText(result.value);
						status.textContent = 'Copied.';
					} catch (error) {
						result.focus();
						result.select();
						status.textContent = 'Text selected. Press Ctrl+C or Command+C to copy.';
					}
				});
			}());
			</script>
		<?php endif; ?>
	</div>
	<?php
}
