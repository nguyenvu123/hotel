<?php
// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function monsterinsights_ads_output_after_script_old( $options ) {
	$track_user    = monsterinsights_track_user();
	$ua            = monsterinsights_get_ua_to_output();

	if ( $track_user && $ua ) {
		ob_start();
		echo PHP_EOL;
		?>
<!-- MonsterInsights Ads Tracking -->
<script type="text/javascript">
<?php
echo "window.google_analytics_uacct = '" . $tracking_code . "';" . PHP_EOL . PHP_EOL;
?>
</script>
<!-- End MonsterInsights Ads Tracking -->
<?php
		echo PHP_EOL;
		echo ob_get_clean();
	}

}
add_action( 'monsterinsights_tracking_after_analytics', 'monsterinsights_ads_output_after_script_old' );