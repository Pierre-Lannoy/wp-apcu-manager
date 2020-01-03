<?php
/**
 * Provide a admin-facing view for the plugin
 *
 * This file is used to markup the admin-facing aspects of the plugin.
 *
 * @package    Plugin
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   1.0.0
 */

use APCuManager\System\Role;

wp_enqueue_script( 'apcm-moment-with-locale' );
wp_enqueue_script( 'apcm-daterangepicker' );
wp_enqueue_script( 'apcm-chartist' );
wp_enqueue_script( 'apcm-chartist-tooltip' );
wp_enqueue_script( APCM_ASSETS_ID );
wp_enqueue_style( APCM_ASSETS_ID );
wp_enqueue_style( 'apcm-daterangepicker' );
wp_enqueue_style( 'apcm-tooltip' );
wp_enqueue_style( 'apcm-chartist' );
wp_enqueue_style( 'apcm-chartist-tooltip' );


?>

<div class="wrap">
	<div class="apcm-dashboard">
		<div class="apcm-row">
			<?php echo $analytics->get_title_bar() ?>
		</div>
        <div class="apcm-row">
	        <?php echo $analytics->get_kpi_bar() ?>
        </div>
        <div class="apcm-row">
	        <?php echo $analytics->get_main_chart() ?>
        </div>
        <div class="apcm-row">
			<?php echo $analytics->get_events_list() ?>
        </div>
	</div>
</div>
