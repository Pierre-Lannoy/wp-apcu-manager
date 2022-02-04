<?php
/**
 * Provide a admin-facing tools for the plugin
 *
 * This file is used to markup the admin-facing aspects of the plugin.
 *
 * @package    Plugin
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   1.0.0
 */

use APCuManager\Plugin\Feature\Objects;

$objects = new Objects();
$objects->prepare_items();

wp_enqueue_script( APCM_ASSETS_ID );
wp_enqueue_style( APCM_ASSETS_ID );

add_thickbox();

?>

<div class="wrap">
	<h2><?php echo esc_html__( 'APCu Management', 'apcu-manager' ); ?></h2>
	<?php settings_errors(); ?>
	<?php $objects->warning(); ?>
	<?php $objects->views(); ?>
    <form id="apcm-tools" method="post" action="<?php echo $objects->get_url(); ?>">
        <input type="hidden" name="page" value="apcm-tools" />
	    <?php $objects->display(); ?>
    </form>
</div>
