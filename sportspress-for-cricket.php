<?php
/*
 * Plugin Name: SportsPress for Cricket
 * Plugin URI: http://themeboy.com/
 * Description: A suite of cricket features for SportsPress.
 * Author: ThemeBoy
 * Author URI: http://themeboy.com/
 * Version: 0.9.2
 *
 * Text Domain: sportspress-for-cricket
 * Domain Path: /languages/
*/

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'SportsPress_Cricket' ) ) :

/**
 * Main SportsPress Cricket Class
 *
 * @class SportsPress_Cricket
 * @version	0.9.2
 */
class SportsPress_Cricket {

	/**
	 * Constructor.
	 */
	public function __construct() {
		// Define constants
		$this->define_constants();

		// Include required files
		$this->includes();

		// Require SportsPress core
		add_action( 'tgmpa_register', array( $this, 'require_core' ) );

		// Enqueue scripts
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts' ), 30 );

		// Change text to reflect cricket terminology
		add_filter( 'gettext', array( $this, 'gettext' ), 20, 3 );

		// Add extras to event performance
		add_action( 'sportspress_event_performance_meta_box_table_footer', array( $this, 'meta_box_table_footer' ), 10, 7 );
		add_action( 'sportspress_event_performance_table_footer', array( $this, 'table_footer' ), 10, 4 );
		add_filter( 'sportspress_event_performance_table_total_value', array( $this, 'table_total_value' ), 10, 3 );
		add_filter( 'sportspress_event_performance_split_team_players', array( $this, 'split_team_players' ) );
		add_filter( 'sportspress_event_performance_split_team_split_position_subdata', array( $this, 'subdata' ), 20, 3 );
		add_filter( 'sportspress_event_performance_show_footer', '__return_true' );

		// Display subs separately
		add_action( 'sportspress_after_event_performance_table', array( $this, 'subs' ), 10, 4 );
		add_filter( 'sportspress_event_performance_players', array( $this, 'players' ), 10, 2 );

		// Add bowling order
		add_filter( 'sportspress_event_performance_split_team_split_position_subdata', array( $this, 'performance_order' ), 10, 3 );
		add_filter( 'sportspress_event_performance_split_position_subdata', array( $this, 'performance_order' ), 10, 3 );

		// Add notes table and display in performance
		add_filter( 'sportspress_event_performance_tabs_admin', array( $this, 'performance_tabs' ) );
		add_action( 'sportspress_after_event_performance_table_admin', array( $this, 'adming_batting_performance' ), 10, 4 );
		add_filter( 'sportspress_event_performance_labels', array( $this, 'performance_labels' ) );
		add_filter( 'sportspress_event_performance_allowed_labels', array( $this, 'performance_labels' ), 10, 2 );
		add_filter( 'sportspress_event_performance_labels_admin', array( $this, 'admin_labels' ) );
		add_filter( 'sportspress_get_event_performance', array( $this, 'event_performance' ) );
		add_filter( 'sportspress_event_auto_result_bypass_keys', array( $this, 'bypass_keys' ) );

		// Display formatted results
		add_filter( 'sportspress_event_logo_options', array( $this, 'event_logo_options' ) );
		add_filter( 'sportspress_event_logos_team_result', array( $this, 'format_result' ), 10, 3 );
		add_filter( 'sportspress_event_team_result_admin', array( $this, 'format_result' ), 10, 3 );
		add_filter( 'sportspress_calendar_team_result_admin', array( $this, 'format_result' ), 10, 3 );
		add_filter( 'sportspress_event_list_main_results', array( $this, 'format_results' ), 10, 2 );
		add_filter( 'sportspress_event_blocks_team_result_or_time', array( $this, 'format_results' ), 10, 2 );
	}

	/**
	 * Define constants.
	*/
	private function define_constants() {
		if ( !defined( 'SP_CRICKET_VERSION' ) )
			define( 'SP_CRICKET_VERSION', '0.9.2' );

		if ( !defined( 'SP_CRICKET_URL' ) )
			define( 'SP_CRICKET_URL', plugin_dir_url( __FILE__ ) );

		if ( !defined( 'SP_CRICKET_DIR' ) )
			define( 'SP_CRICKET_DIR', plugin_dir_path( __FILE__ ) );
	}

	/**
	 * Register/queue frontend scripts.
	 *
	 * @access public
	 * @return void
	 */
	public function enqueue_scripts() {
		wp_enqueue_script( 'sportspress-cricket', SP_CRICKET_URL .'js/sportspress-cricket.js', array( 'jquery' ), SP_CRICKET_VERSION, true );
	}

	/**
	 * Enqueue styles.
	 */
	public static function admin_enqueue_scripts() {
		$screen = get_current_screen();

		if ( in_array( $screen->id, array( 'sp_event', 'edit-sp_event' ) ) ) {
			wp_enqueue_script( 'sportspress-cricket-admin', SP_CRICKET_URL . 'js/admin.js', array( 'jquery' ), SP_CRICKET_VERSION, true );
		}

		wp_enqueue_style( 'sportspress-cricket-admin', SP_CRICKET_URL . 'css/admin.css', array( 'sportspress-admin-menu-styles' ), '0.9' );
	}

	/**
	 * Include required files.
	*/
	private function includes() {
		require_once dirname( __FILE__ ) . '/includes/class-tgm-plugin-activation.php';
	}

	/**
	 * Require SportsPress core.
	*/
	public static function require_core() {
		$plugins = array(
			array(
				'name'        => 'SportsPress',
				'slug'        => 'sportspress',
				'required'    => true,
				'is_callable' => array( 'SportsPress', 'instance' ),
			),
		);

		$config = array(
			'default_path' => '',
			'menu'         => 'tgmpa-install-plugins',
			'has_notices'  => true,
			'dismissable'  => true,
			'is_automatic' => true,
			'message'      => '',
			'strings'      => array(
				'nag_type' => 'updated'
			)
		);

		tgmpa( $plugins, $config );
	}

	/** 
	 * Text filter.
	 */
	public function gettext( $translated_text, $untranslated_text, $domain ) {
		if ( $domain == 'sportspress' ) {
			switch ( $untranslated_text ) {
				case 'Events':
					$translated_text = __( 'Matches', 'cricket', 'sportspress-for-cricket' );
					break;
				case 'Event':
					$translated_text = __( 'Match', 'cricket', 'sportspress-for-cricket' );
					break;
				case 'Add New Event':
					$translated_text = __( 'Add New Match', 'cricket', 'sportspress-for-cricket' );
					break;
				case 'Edit Event':
					$translated_text = __( 'Edit Match', 'cricket', 'sportspress-for-cricket' );
					break;
				case 'View Event':
					$translated_text = __( 'View Match', 'cricket', 'sportspress-for-cricket' );
					break;
				case 'View all events':
					$translated_text = __( 'View all matches', 'cricket', 'sportspress-for-cricket' );
					break;
				case 'Substitute':
				case 'Substituted':
					$translated_text = __( 'Did Not Bat', 'cricket', 'sportspress-for-cricket' );
					break;
			}
		}
		
		return $translated_text;
	}

	/**
	 * Display extras in event edit page.
	*/
	public function meta_box_table_footer( $data = array(), $labels = array(), $team_id = 0, $positions = array(), $status = true, $sortable = true, $numbers = true ) {
		?>
		<tr class="sp-row sp-post sp-extras">
			<?php if ( $sortable ) { ?>
				<td>&nbsp;</td>
			<?php } ?>
			<?php if ( $numbers ) { ?>
				<td>&nbsp;</td>
			<?php } ?>
			<td><strong><?php _e( 'Extras', 'sportspress-for-cricket' ); ?></strong></td>
			<?php if ( ! empty( $positions ) ) { ?>
				<td>&nbsp;</td>
			<?php } ?>
			<?php foreach( $labels as $column => $label ):
				$player_performance = sp_array_value( $data, -1, array() );
				$value = sp_array_value( $player_performance, $column, '' );
				?>
				<td><input type="text" name="sp_players[<?php echo $team_id; ?>][-1][<?php echo $column; ?>]" value="<?php echo $value; ?>" /></td>
			<?php endforeach; ?>
			<?php if ( $status ) { ?>
				<td>&nbsp;</td>
			<?php } ?>
		</tr>
		<?php
	}

	/**
	 * Display extras in event page.
	*/
	public function table_footer( $data = array(), $labels = array(), $position = null, $performance_ids = null ) {
		$show_players = get_option( 'sportspress_event_show_players', 'yes' ) === 'yes' ? true : false;
		$show_numbers = get_option( 'sportspress_event_show_player_numbers', 'yes' ) === 'yes' ? true : false;
		$mode = get_option( 'sportspress_event_performance_mode', 'values' );

		$row = sp_array_value( $data, -1, array() );
		$row = array_filter( $row );
		$row = array_intersect_key( $row, $labels );
		if ( ! empty( $row ) ) {
			?>
			<tr class="sp-extras-row <?php echo ( $i % 2 == 0 ? 'odd' : 'even' ); ?>">
				<?php
				if ( $show_players ):
					if ( $show_numbers ) {
						echo '<td class="data-number">&nbsp;</td>';
					}
					echo '<td class="data-name">' . __( 'Extras', 'sportspress-for-cricket' ) . '</td>';
				endif;

				$row = sp_array_value( $data, -1, array() );

				if ( $mode == 'icons' ) echo '<td class="sp-performance-icons">';

				foreach ( $labels as $key => $label ):
					if ( 'name' == $key )
						continue;
					if ( isset( $position ) && 'position' == $key )
						continue;
					if ( $key == 'position' ):
						$value = '&nbsp;';
					elseif ( array_key_exists( $key, $row ) && $row[ $key ] != '' ):
						$value = $row[ $key ];
					else:
						$value = '&nbsp;';
					endif;

					if ( $mode == 'values' ):
						echo '<td class="data-' . $key . '">' . $value . '</td>';
					elseif ( intval( $value ) && $mode == 'icons' ):
						$performance_id = sp_array_value( $performance_ids, $key, null );
						if ( $performance_id && has_post_thumbnail( $performance_id ) ):
							echo str_repeat( get_the_post_thumbnail( $performance_id, 'sportspress-fit-mini' ) . ' ', $value );
						endif;
					endif;
				endforeach;

				if ( $mode == 'icons' ) echo '</td>';
				?>
			</tr>
			<?php
		}
	}

	/**
	 * Add extras to performance total.
	*/
	public function table_total_value( $value = 0, $data = array(), $key = null ) {
		$value += sp_array_value( sp_array_value( $data, -1, array() ), $key, 0 );
		return $value;
	}

	/**
	 * Add extra player row to split team players.
	*/
	public function split_team_players( $players = array() ) {
		$players[] = -1;
		return $players;
	}

	/**
	 * Add extra subdata to split team split position players.
	*/
	public function subdata( $subdata = array(), $data = array(), $index = 0 ) {
		$subdata[-1] = sp_array_value( $data, -1, array() );
		return $subdata;
	}

	/**
	 * Remove subs from main box score.
	*/
	public function players( $data = array(), $lineups = array() ) {
		return $lineups;
	}

	/**
	 * Display subs in own section.
	*/
	public function subs( $data = array(), $lineups = array(), $subs = array(), $class = '' ) {
		if ( empty( $subs ) || '0' !== substr( $class, -1 ) ) return;

		$names = array();

		foreach ( $subs as $id => $void ) {
			$name = get_the_title( $id );
			if ( $name ) {
				$link = get_post_permalink( $id );
				$names[] = '<a href="' . $link . '">' . $name . '</a>';
			}
		}
		?>
		<p class="sp-event-performance-simple-subs sp-align-left">
			<?php printf( __( 'Did not bat: %s', 'sportspress-for-cricket' ), implode( ', ', $names ) ); ?>
		</p>
		<?php
	}

	/**
	 * Sort batsmen by batting order.
	*/
	public function performance_order( $subdata = array(), $data = array(), $index = 0 ) {
		if ( 0 == $index ) {
			uasort( $subdata, array( $this, 'sort_by_batting_order' ) );
		}
		return $subdata;
	}

	/**
	 * Sort array by batting order.
	*/
	public function sort_by_batting_order( $a, $b ) {
		return sp_array_value( $a, '_order', 0 ) - sp_array_value( $b, '_order', 0 );
	}

	/**
	 * Add tab for notes.
	*/
	public function performance_tabs( $tabs = array() ) {
		$tabs['batting'] = __( 'Batting', 'sportspress-for-cricket' );
		return $tabs;
	}

	/**
	 * Add tab for batting.
	*/
	public function	adming_batting_performance( $labels = array(), $columns = array(), $data = array(), $team_id = 0 ) {
		?>
		<div class="sp-data-table-container hidden">
			<table class="widefat sp-data-table sp-performance-batting-table">
				<thead>
					<tr>
						<th><?php _e( 'Order', 'sportspress-for-cricket' ); ?></th>
						<th><?php _e( 'Player', 'sportspress-for-cricket' ); ?></th>
						<th><?php _e( 'Notes', 'sportspress-for-cricket' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $data as $player_id => $player_performance ) { ?>
						<?php if ( $player_id <= 0 ) continue; ?>
						<tr class="sp-row sp-post">
							<td><input type="text" class="small-text" name="sp_players[<?php echo $team_id; ?>][<?php echo $player_id; ?>][_order]" value="<?php echo sp_array_value( sp_array_value( $data, $player_id, array() ), '_order', '' ); ?>"></td>
							<td><?php echo get_the_title( $player_id ); ?></td>
							<td><input type="text" class="widefat" name="sp_players[<?php echo $team_id; ?>][<?php echo $player_id; ?>][_notes]" value="<?php echo sp_array_value( sp_array_value( $data, $player_id, array() ), '_notes', '' ); ?>"></td>
						</tr>
					<?php } ?>
				<tfoot>
					<tr>
						<td>&nbsp;</td>
						<td><strong><?php _e( 'Extras', 'sportspress-for-cricket' ); ?></strong></td>
						<td><input type="text" class="widefat" name="sp_players[<?php echo $team_id; ?>][-1][_notes]" value="<?php echo sp_array_value( sp_array_value( $data, -1, array() ), '_notes', '' ); ?>"></td>
					</tr>
				</tfoot>
			</table>
		</div>
		<?php
	}

	/**
	 * Add notes to performance labels.
	*/
	public function performance_labels( $labels = array(), $index = 0 ) {
		if ( 0 !== $index ) return $labels;
		$labels = array( '_notes' => '&nbsp;' ) + $labels;
		return $labels;
	}

	/**
	 * Remove notes from admin labels.
	*/
	public function admin_labels( $labels ) {
		unset( $labels['_notes'] );
		return $labels;
	}

	/**
	 * Append notes with blank space to prevent rendering as zero.
	*/
	public function event_performance( $data = array() ) {
		if ( ! is_array( $data ) || 0 == sizeof( $data ) ) return $data;

		foreach ( $data as $team_id => $player ) {
			if ( ! is_array( $player ) ) continue;

			foreach ( $player as $player_id => $stats ) {
				$data[ $team_id ][ $player_id ]['_notes'] = sp_array_value( $stats, '_notes', '' ) . ' ';
			}
		}

		return $data;
	}

	/**
	 * Bypass notes when determining automatic results.
	*/
	public function bypass_keys( $keys ) {
		$keys[] = '_order';
		$keys[] = '_notes';
		return $keys;
	}

	/**
	 * Add event logo options.
	*/
	public function event_logo_options( $options = array() ) {
		$options[] = array(
			'title' 	=> __( 'Delimiter', 'sportspress-for-cricket' ),
			'id' 		=> 'sportspress_event_logos_results_delimiter',
			'class' 	=> 'small-text',
			'default'	=> '/',
			'type' 		=> 'text',
		);

		$options[] = array(
			'title'     => __( 'Format', 'sportspress-for-cricket' ),
			'desc' 		=> __( 'Reverse order', 'sportspress-for-cricket' ),
			'id' 		=> 'sportspress_event_logos_reverse_results_format',
			'default'	=> 'no',
			'type' 		=> 'checkbox',
		);
		return $options;
	}

	/**
	 * Format single result.
	*/
	public function format_result( $result = '', $id = 0, $team = 0 ) {
		if ( '' === $result || ! $id || ! $team ) return $result;
		$results = sp_get_results( $id );
		$team_results = sp_array_value( $results, $team, array() );
		if ( ! is_array( $team_results ) || 0 == sizeof( $team_results ) ) return $result;
		$main = sp_get_main_result_option();
		while ( key( $team_results ) !== $main ) {
			next( $team_results );
		}
		$val = next( $team_results );
		if ( false === $val ) {
			$val = reset( $team_results );
		}
		if ( isset( $val ) && ! is_array( $val ) ) {
			$delimiter = get_option( 'sportspress_event_logos_results_delimiter', '/' );
			$reverse = get_option( 'sportspress_event_logos_reverse_results_format', 'no' );
			if ( 'yes' == $reverse ) {
				$result = $val . $delimiter . $result;
			} else {
				$result .= $delimiter . $val;
			}
		}
		return $result;
	}

	/**
	 * Format results.
	*/
	public function format_results( $results = array(), $id = 0 ) {
		if ( ! is_array( $results ) || 1 >= sizeof( $results ) || ! $id ) return $results;

		$teams = get_post_meta( $id, 'sp_team' );

		foreach ( $results as $team => $result ) {
			$results[ $team ] = self::format_result( $result, $id, sp_array_value( $teams, $team, 0 ) );
		}

		return $results;
	}
}

endif;

new SportsPress_Cricket();
