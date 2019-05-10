<?php
/**
 * Handles loading the Page Insights report data from the relay and the data life-cycle.
 *
 * @package monsterinsights-page-insights
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class MonsterInsights_Page_Insights_Reports
 */
final class MonsterInsights_Report_Page_Insights extends MonsterInsights_Report {

	/**
	 * The report class.
	 *
	 * @var string
	 */
	public $class = 'MonsterInsights_Report_Page_Insights';

	/**
	 * The report unique identifier.
	 *
	 * @var string
	 */
	public $name = 'pageinsights';

	/**
	 * The report version.
	 *
	 * @var string
	 */
	public $version = '1.0.0';

	/**
	 * The license level needed to access the report.
	 *
	 * @var string
	 */
	public $level = 'plus';

	/**
	 * The path for which we are fetching the report.
	 *
	 * @var string
	 */
	public $path;

	/**
	 * Primary class constructor.
	 *
	 * @access public
	 * @since 6.0.0
	 */
	public function __construct() {
		$this->title = __( 'Page Insights', 'monsterinsights-pageinsights' );
		parent::__construct();
	}

	/**
	 * Custom get_data to handle caching specific to this report.
	 *
	 * @param array $args The arguments for grabbing the data from the Relay.
	 *
	 * @return array
	 */
	public function get_data( $args = array() ) {

		if ( ! MonsterInsights()->license->license_can( $this->level ) ) {
			return array(
				'success' => true,
				'upgrade' => true,
				'data'    => array(),
			);
		}

		$site_auth = MonsterInsights()->auth->get_viewname();
		$ms_auth   = is_multisite() && MonsterInsights()->auth->get_network_viewname();

		if ( empty( $site_auth ) && empty( $ms_auth ) ) {
			return array(
				'success' => false,
				'error'   => __( 'You must authenticate with MonsterInsights to use reports.', 'monsterinsights-pageinsights' ),
				'data'    => array(),
			);
		}

		if ( empty( $_REQUEST['post_id'] ) ) {
			return array(
				'success' => false,
				// Translators: %s is the name of the post type.
				'error'   => esc_html__( 'Missing post id parameter.', 'monsterinsights-pageinsights' ),
				'data'    => array(),
			);
		}

		$post_id         = absint( $_REQUEST['post_id'] );
		$author_can_view = current_user_can( 'edit_post', $post_id ) && apply_filters( 'monsterinsights_pageinsights_author_can_view', false );
		if ( ! $author_can_view && ! current_user_can( 'monsterinsights_view_dashboard' ) ) {
			return array(
				'success' => false,
				// Translators: %s is the name of the post type.
				'error'   => sprintf( esc_html__( 'You are not allowed to view reports for this %s.', 'monsterinsights-pageinsights' ), get_post_type( $post_id ) ),
				'data'    => array(),
			);
		}

		$error = apply_filters( 'monsterinsights_reports_abstract_get_data_pre_cache', false, $args, $this->name );
		if ( $error ) {
			return apply_filters( 'monsterinsights_reports_handle_error_message', array(
				'success' => false,
				'error'   => $error,
				'data'    => array(),
			) );
		}

		// Check if the data exists in the cache.
		$check_cache = MonsterInsights_Page_Insights_Cache::get_instance()->get( self::get_path_by_post_id( $post_id ) );

		// If there is data return now to prevent additional requests.
		if ( $check_cache ) {
			return array(
				'success' => true,
				'data'    => $check_cache,
			);
		}

		// If the data was recently checked, prevent additional calls to the Relay as the data there is also cached.
		if ( ! MonsterInsights_Page_Insights_Background::should_fetch() ) {
			return array(
				'success' => true,
				'data'    => array(
					'page_path' => self::get_path_by_post_id( $post_id ),
				),
			);
		}

		$api_options = array();
		if ( ! $site_auth && $ms_auth ) {
			$api_options['network'] = true;
		}

		$api = new MonsterInsights_API_Request( 'analytics/reports/' . $this->name . '/single', $api_options, 'GET' );

		$additional_data = $this->additional_data();

		if ( ! empty( $additional_data ) ) {
			$api->set_additional_data( $additional_data );
		}

		$report_data = $api->request();

		if ( is_wp_error( $report_data ) ) {
			return array(
				'success' => false,
				'error'   => $report_data->get_error_message(),
				'data'    => array(),
			);
		} else {

			// Data pulled successfully.
			// Save the data to the cache.
			MonsterInsights_Page_Insights_Cache::get_instance()->set( $report_data['data']['page_path'], $report_data['data'] );
			// Initiate the full data pulling.
			MonsterInsights_Page_Insights_Background::start_fetch();

			// Return the page-specific data.
			return array(
				'success' => true,
				'data'    => $report_data['data'],
			);
		}
	}

	/**
	 * The report-specific output.
	 *
	 * @param array $data The report data from cache or from Relay.
	 *
	 * @return string
	 */
	public function get_report_html( $data = array() ) {

		check_ajax_referer( 'mi-admin-nonce', 'security' );

		ob_start();

		$interval = '30days';
		if ( isset( $_REQUEST['interval'] ) ) {
			$interval = sanitize_text_field( wp_unslash( $_REQUEST['interval'] ) );
		}
		?>
		<div class="monsterinsights-pageinsights-report-content monsterinsights-pageinsights-interval-<?php echo esc_attr( $interval ); ?>">
			<?php

			$report_data = [];
			if ( isset( $data[ $interval ] ) ) {
				$report_data = $data[ $interval ];
			}
			$report_data = wp_parse_args( $report_data, self::get_default_metrics_value() );
			$labels      = self::get_metrics_labels();

			foreach ( $report_data as $metric_name => $metric_value ) {
				$label        = isset( $labels[ $metric_name ] ) ? $labels[ $metric_name ] : $metric_name;
				$metric_value = self::prepare_metric( $metric_value, $metric_name );
				?>
				<div class="monsterinsights-pageinsights-report-box monsterinsights-pageinsights-report-<?php echo esc_attr( $metric_name ); ?>">
					<span class="monsterinsights-pageinsights-report-metric"><?php echo esc_html( $label ); ?></span>
					<span class="monsterinsights-pageinsights-report-value"><?php echo esc_html( $metric_value ); ?></span>
				</div>
				<?php
			}

			?>
		</div>
		<?php

		return ob_get_clean();

	}

	/**
	 * Add the page-specific path to the request.
	 *
	 * @return array|WP_Error
	 */
	public function additional_data() {

		// This was checked in the get_data function above.
		$post_id = absint( $_REQUEST['post_id'] );

		return array(
			'path' => self::get_path_by_post_id( $post_id ),
		);

	}

	/**
	 * Get a consistent path from a post id.
	 *
	 * @param int $post_id The post id for which to grab the path.
	 *
	 * @return string
	 */
	public function get_path_by_post_id( $post_id ) {

		if ( ! isset( $this->path ) ) {
			$this->path = '/' . trailingslashit( get_page_uri( $post_id ) );
		}

		return $this->path;

	}

	/**
	 * Get the metrics with their default values.
	 *
	 * @return array
	 */
	public static function get_default_metrics_value() {

		return apply_filters( 'monsterinsights_pageinsights_report_metrics_default', array(
			'bouncerate'   => 0,
			'entrances'    => 0,
			'pageviews'    => 0,
			'timeonpage'   => 0,
			'pageloadtime' => 0,
			'exits'        => 0,
		) );

	}

	/**
	 * Get metrics labels.
	 *
	 * @return array
	 */
	public static function get_metrics_labels() {

		return apply_filters( 'monsterinsights_pageinsights_report_metrics_labels', array(
			'bouncerate'   => esc_html__( 'Bounce Rate', 'monsterinsights-pageinsights' ),
			'entrances'    => esc_html__( 'Entrances', 'monsterinsights-pageinsights' ),
			'pageviews'    => esc_html__( 'Page Views', 'monsterinsights-pageinsights' ),
			'timeonpage'   => esc_html__( 'Time on Page', 'monsterinsights-pageinsights' ),
			'pageloadtime' => esc_html__( 'Page Load Time', 'monsterinsights-pageinsights' ),
			'exits'        => esc_html__( 'Exits', 'monsterinsights-pageinsights' ),
		) );

	}

	/**
	 * Some metrics need to be formatted before output.
	 *
	 * @param string|int $value The metric value.
	 * @param string     $name The name of the metric.
	 *
	 * @return string
	 */
	public static function prepare_metric( $value, $name ) {

		switch ( $name ) {
			case 'bouncerate':
				$value = number_format( $value, 2 ) . '%';
				break;
			case 'timeonpage':
				$value = empty( $value ) ? 0 . 's' : $value;
				break;
			case 'pageloadtime':
				$value = empty( $value ) ? 0 : $value;
				$value .= 's';
				break;
		}

		$value = apply_filters( 'monsterinsights_pageinsights_prepare_metric', $value, $name );

		return $value;
	}
}
