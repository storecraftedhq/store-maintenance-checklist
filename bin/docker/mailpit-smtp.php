<?php
/**
 * Docker-only: route wp_mail() through Mailpit SMTP.
 *
 * Bound into wp-content/mu-plugins/ by docker-compose.yml.
 * Not included in the distribution zip (.distignore covers /bin/).
 *
 * @package StoreCrafted
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action(
	'phpmailer_init',
	static function ( $phpmailer ): void {
		$phpmailer->isSMTP();
		$phpmailer->Host       = 'mailpit';
		$phpmailer->Port       = 1025;
		$phpmailer->SMTPAuth   = false;
		$phpmailer->SMTPSecure = false;
	}
);
