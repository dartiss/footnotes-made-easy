<?php
/*
Plugin Name: Footnotes Made Easy
Plugin URI: https://wordpress.org/plugins/footnotes-made-easy/
Description: Allows post authors to easily add and manage footnotes in posts.
Version: 1.0.1
Author: David Artiss
Author URI: https://artiss.blog
Text Domain: footnotes-made-easy
*/

/**
* Footnotes Made Easy
*
* Easily add footnotes to a post
*
* @package	footnotes-made-easy
* @since	1.0
*/

// Instantiate the class

$swas_wp_footnotes = new swas_wp_footnotes();

// Encapsulate in a class

class swas_wp_footnotes {

	private $current_options;
	private $default_options;

	const OPTIONS_VERSION = "5"; // Incremented when the options array changes

	// Constructor

	 function __construct() {

		// Define the implemented option styles

		$this -> styles = array(
								'decimal' => '1,2...10',
								'decimal-leading-zero' => '01, 02...10',
								'lower-alpha' => 'a,b...j',
								'upper-alpha' => 'A,B...J',
								'lower-roman' => 'i,ii...x',
								'upper-roman' => 'I,II...X',
								'symbol' => 'Symbol'
								);

		// Define default options

		$this->default_options = array('superscript' => true,
									  'pre_backlink' => ' [',
									  'backlink' => '&#8617;',
									  'post_backlink' => ']',
									  'pre_identifier' => '',
									  'inner_pre_identifier' => '',
									  'list_style_type' => 'decimal',
									  'list_style_symbol' => '&dagger;',
									  'inner_post_identifier' => '',
									  'post_identifier' => '',
									  'pre_footnotes' => '',
									  'post_footnotes' => '',
									  'no_display_home' => false,
									  'no_display_preview' => false,
									  'no_display_archive' => false,
									  'no_display_date' => false,
									  'no_display_category' => false,
									  'no_display_search' => false,
									  'no_display_feed' => false,
									  'combine_identical_notes' => true,
									  'priority' => 11,
									  'footnotes_open' => ' ((',
									  'footnotes_close' => '))',
									  'pretty_tooltips' => false,
									  'version' => self::OPTIONS_VERSION
									  );

		// Get the current settings or setup some defaults if needed

		if ( !$this->current_options === get_option( 'swas_footnote_options' ) ){
			$this->current_options = $this->default_options;
			update_option( 'swas_footnote_options', $this->current_options );
		} else {

			// Set any unset options

			if ( !isset( $this->current_options[ 'version' ] ) || $this->current_options[ 'version' ] !== self::OPTIONS_VERSION) {
				foreach ( $this->default_options as $key => $value ) {
					if ( !isset( $this->current_options[ $key ] ) ) {
						$this->current_options[ $key ] = $value;
					}
				}
				$this->current_options[ 'version' ] = self::OPTIONS_VERSION;
				update_option( 'swas_footnote_options', $this->current_options );
			}
		}

		if( !empty($_POST[ 'save_options' ] ) ) {

			$footnotes_options[ 'superscript' ] = ( array_key_exists( 'superscript', $_POST ) ) ? true : false;

			$footnotes_options[ 'pre_backlink' ] = sanitize_text_field( $_POST[ 'pre_backlink' ] );
			$footnotes_options[ 'backlink' ] = sanitize_text_field( $_POST[ 'backlink' ] );
			$footnotes_options[ 'post_backlink' ] = sanitize_text_field( $_POST[ 'post_backlink' ] );

			$footnotes_options[ 'pre_identifier' ] = sanitize_text_field( $_POST[ 'pre_identifier' ] );
			$footnotes_options[ 'inner_pre_identifier' ] = sanitize_text_field( $_POST[ 'inner_pre_identifier' ] );
			$footnotes_options[ 'list_style_type' ] = sanitize_text_field( $_POST[ 'list_style_type' ] );
			$footnotes_options[ 'inner_post_identifier' ] = sanitize_text_field( $_POST[ 'inner_post_identifier' ] );
			$footnotes_options[ 'post_identifier' ] = sanitize_text_field( $_POST[ 'post_identifier' ] );
			$footnotes_options[ 'list_style_symbol' ] = sanitize_text_field( $_POST[ 'list_style_symbol' ] );


			$footnotes_options[ 'pre_footnotes' ] = $_POST[ 'pre_footnotes' ];
			$footnotes_options[ 'post_footnotes' ] = $_POST[ 'post_footnotes' ];

			$footnotes_options[ 'no_display_home' ] = ( array_key_exists( 'no_display_home', $_POST ) ) ? true : false;
			$footnotes_options[ 'no_display_preview' ] = ( array_key_exists( 'no_display_preview', $_POST ) ) ? true : false;
			$footnotes_options[ 'no_display_archive' ] = ( array_key_exists( 'no_display_archive', $_POST ) ) ? true : false;
			$footnotes_options[ 'no_display_date' ] = ( array_key_exists( 'no_display_date', $_POST ) ) ? true : false;
			$footnotes_options[ 'no_display_category' ] = ( array_key_exists( 'no_display_category', $_POST ) ) ? true : false;
			$footnotes_options[ 'no_display_search' ] = ( array_key_exists( 'no_display_search', $_POST ) ) ? true : false;
			$footnotes_options[ 'no_display_feed' ] = ( array_key_exists( 'no_display_feed', $_POST ) ) ? true : false;

			$footnotes_options[ 'combine_identical_notes' ] = ( array_key_exists( 'combine_identical_notes', $_POST ) ) ? true : false;
			$footnotes_options[ 'priority' ] = sanitize_text_field( $_POST[ 'priority' ] );

			$footnotes_options[ 'footnotes_open' ] = sanitize_text_field( $_POST[ 'footnotes_open' ] );
			$footnotes_options[ 'footnotes_close' ] = sanitize_text_field( $_POST[ 'footnotes_close' ] );

			$footnotes_options[ 'pretty_tooltips' ] = ( array_key_exists( 'pretty_tooltips', $_POST ) ) ? true : false;

			update_option( 'swas_footnote_options', $footnotes_options );

		} elseif( !empty( $_POST[ 'reset_options' ] ) ) {

			update_option( 'swas_footnote_options', '' );
			update_option( 'swas_footnote_options', $this->default_options );

		}

		// Hook me up

		add_action( 'the_content', array( $this, 'process' ), $this->current_options[ 'priority' ] );
		add_action( 'admin_menu', array( $this, 'add_options_page' ) ); 		// Insert the Admin panel.
		add_action( 'wp_head', array( $this, 'insert_styles' ) );
		if ( $this->current_options[ 'pretty_tooltips' ] ) add_action( 'wp_enqueue_scripts', array( $this, 'tooltip_scripts' ) );
	}

	/**
	* Searches the text and extracts footnotes
	*
	* Adds the identifier links and creats footnotes list
	*
	* @since	1.0
	*
	* @param	$data	string	The content of the post
	* @return			string 	The new content with footnotes generated
	*/

	function process( $data ) {

		global $post;

		// Check for and setup the starting number

		$start_number = ( 1 === preg_match( "|<!\-\-startnum=(\d+)\-\->|", $data,$start_number_array ) ) ? $start_number_array[ 1 ] : 1;

		// Regex extraction of all footnotes (or return if there are none)

		if ( ! preg_match_all( "/(" . preg_quote( $this->current_options[ 'footnotes_open' ], "/" ) . ")(.*)(" . preg_quote( $this->current_options[ 'footnotes_close' ], "/" ) . ")/Us", $data, $identifiers, PREG_SET_ORDER ) ) {
			return $data;
		}

		// Check whether we are displaying them or not

		$display = true;
		if ( $this->current_options[ 'no_display_home' ] && is_home() ) $display = false;
		if ( $this->current_options[ 'no_display_archive' ] && is_archive() ) $display = false;
		if ( $this->current_options[ 'no_display_date' ] && is_date() ) $display = false;
		if ( $this->current_options[ 'no_display_category' ] && is_category() ) $display = false;
		if ( $this->current_options[ 'no_display_search' ] && is_search() ) $display = false;
		if ( $this->current_options[ 'no_display_feed' ] && is_feed() ) $display = false;
		if ( $this->current_options[ 'no_display_preview' ] && is_preview() ) $display = false;

		$footnotes = array();

		// Check if this post is using a different list style to the settings

		if ( get_post_meta( $post->ID, 'footnote_style', true ) && array_key_exists( get_post_meta( $post->ID, 'footnote_style', true ), $this->styles ) ) {
			$style = get_post_meta( $post->ID, 'footnote_style', true );
		} else {
			$style = $this->current_options[ 'list_style_type' ];
		}

		// Create 'em

		for ( $i = 0; $i < count( $identifiers ); $i++ ){

			// Look for ref: and replace in identifiers array

			if ( 'ref:' === substr( $identifiers[ $i ][ 2 ], 0, 4 ) ){
				$ref = ( int )substr( $identifiers[ $i ][ 2 ],4 );
				$identifiers[ $i ][ 'text' ] = $identifiers[ $ref-1 ][ 2 ];
			}else{
				$identifiers[ $i ][ 'text' ] = $identifiers[ $i ][ 2 ];
			}

			// if we're combining identical notes check if we've already got one like this & record keys

			if ( $this->current_options[ 'combine_identical_notes' ] ){
				for ( $j = 0; $j < count( $footnotes ); $j++ ){
					if ( $footnotes[ $j ][ 'text' ] === $identifiers[ $i ][ 'text' ] ){
						$identifiers[ $i ][ 'use_footnote' ] = $j;
						$footnotes[ $j ][ 'identifiers' ][] = $i;
						break;
					}
				}
			}

			if ( !isset( $identifiers[ $i ][ 'use_footnote' ] ) ){

				// Add footnote and record the key

				$identifiers[ $i ][ 'use_footnote' ] = count( $footnotes );
				$footnotes[ $identifiers[ $i ][ 'use_footnote' ] ][ 'text' ] = $identifiers[ $i ][ 'text' ];
				$footnotes[ $identifiers[ $i ][ 'use_footnote' ] ][ 'symbol' ] = isset( $identifiers[ $i ][ 'symbol' ] ) ? $identifiers[ $i ][ 'symbol' ] : '';
				$footnotes[ $identifiers[ $i ][ 'use_footnote' ] ][ 'identifiers' ][] = $i;
			}
		}

		// Footnotes and identifiers are stored in the array

		$use_full_link = false;
		if ( is_feed() ) $use_full_link = true;

		if ( is_preview() ) $use_full_link = false;

		// Display identifiers

		foreach ( $identifiers as $key => $value ) {

			$id_id = "identifier_" . $key . "_" . $post->ID;
			$id_num = ( $style === 'decimal' ) ? $value[ 'use_footnote' ] + $start_number : $this->convert_num( $value[ 'use_footnote' ] + $start_number, $style, count( $footnotes ) );
			$id_href = ( ( $use_full_link ) ? get_permalink( $post->ID ) : '' ) . "#footnote_" . $value[ 'use_footnote' ] . "_" . $post->ID;
			$id_title = str_replace( '"', "&quot;", htmlentities( html_entity_decode( strip_tags( $value[ 'text' ] ), ENT_QUOTES, 'UTF-8' ), ENT_QUOTES, 'UTF-8' ) );
			$id_replace = $this->current_options[ 'pre_identifier' ] . '<a href="' . $id_href . '" id="' . $id_id . '" class="footnote-link footnote-identifier-link" title="' . $id_title . '">' . $this->current_options[ 'inner_pre_identifier' ] . $id_num . $this->current_options[ 'inner_post_identifier' ] . '</a>' . $this->current_options[ 'post_identifier' ];
			if ( $this->current_options[ 'superscript' ] ) $id_replace = '<sup>' . $id_replace . '</sup>';
			if ( $display ) $data = substr_replace( $data, $id_replace, strpos( $data, $value[ 0 ] ), strlen( $value[ 0 ] ) );
			else $data = substr_replace( $data, '', strpos( $data,$value[ 0 ] ),strlen( $value[ 0 ] ) );
		}

		// Display footnotes

		if ( $display ) {

			$footnotes_markup = '';
			$start = ( $start_number !== 1 ) ? 'start="' . $start_number . '" ' : '';
			$footnotes_markup = $footnotes_markup . $this->current_options[ 'pre_footnotes' ];

			$footnotes_markup = $footnotes_markup . '<ol ' . $start . 'class="footnotes">';
			foreach ( $footnotes as $key => $value ) {
				$footnotes_markup = $footnotes_markup . '<li id="footnote_' . $key . '_' . $post->ID . '" class="footnote"';
				if ( 'symbol' === $style ) {
					$footnotes_markup = $footnotes_markup . ' style="list-style-type:none;"';
				} elseif( $style !== $this->current_options[ 'list_style_type' ] ) {
					$footnotes_markup = $footnotes_markup . ' style="list-style-type:' . $style . ';"';
				}
				$footnotes_markup = $footnotes_markup . '>';
				if ( 'symbol' === $style ) {
					$footnotes_markup = $footnotes_markup . '<span class="symbol">' . $this->convert_num( $key + $start_number, $style, count( $footnotes ) ) . '</span> ';
				}
				$footnotes_markup = $footnotes_markup.$value[ 'text' ];
				if (!is_feed()){
					$footnotes_markup .= '<span class="footnote-back-link-wrapper">';
					foreach( $value[ 'identifiers' ] as $identifier ){
						$footnotes_markup = $footnotes_markup . $this->current_options[ 'pre_backlink' ] . '<a href="' . ( ( $use_full_link ) ? get_permalink( $post->ID ) : '' ) . '#identifier_' . $identifier . '_' . $post->ID . '" class="footnote-link footnote-back-link">' . $this->current_options[ 'backlink' ] . '</a>' . $this->current_options[ 'post_backlink' ];
					}
					$footnotes_markup .= '</span>';
				}
				$footnotes_markup = $footnotes_markup . '</li>';
			}
			$footnotes_markup = $footnotes_markup . '</ol>' . $this->current_options[ 'post_footnotes' ];
		}

		$data = $data . $footnotes_markup;

		return $data;
	}

	/**
	* Options Page
	*
	* Get the options and display the page
	*
	* @since	1.0
	*/

	function footnotes_options_page() {

		$this->current_options = get_option( 'swas_footnote_options' );
		foreach ( $this->current_options as $key=>$setting ) {
			$new_setting[ $key ] = htmlentities( $setting );
		}
		$this->current_options = $new_setting;
		unset( $new_setting );
		include ( dirname(__FILE__) . '/options.php' );
	}

	/**
	* Add Options Help
	*
	* Add help tab to options screen
	*
	* @since	1.0
	*
	*/

	function footnotes_help() {

		global $footnotes_hook;
		$screen = get_current_screen();

		if ( $screen->id !== $footnotes_hook ) { return; }

		$screen -> add_help_tab( array( 'id' => 'footnotes-help-tab', 'title'	=> __( 'Help', 'footnotes-made-easy' ), 'content' => $this->add_help_content() ) );

		$screen -> set_help_sidebar( $this->add_sidebar_content() );

	}

	/**
	* Options Help
	*
	* Return help text for options screen
	*
	* @since	1.0
	*
	* @return	string	Help Text
	*/

	function add_help_content() {

		$help_text = '<p>' . __( 'This screen allows you to specify the default options for the Footnotes Made Easy plugin.', 'footnotes-made-easy' ) . '</p>';
		$help_text .= '<p>' . __( "The identifier is what appears when a footnote is inserted into your page contents. The back-link appear after each footnote, linking back to the identifier.", 'footnotes-made-easy' ) . '</p>';
		$help_text .= '<p>' . __( 'Remember to click the Save Changes button at the bottom of the screen for new settings to take effect.', 'footnotes-made-easy' ) . '</p></h4>';

		return $help_text;
	}

	/**
	* Options Help Sidebar
	*
	* Add a links sidebar to the options help
	*
	* @since	1.0
	*
	* @return	string	Help Text
	*/

	function add_sidebar_content() {

		$help_text = '<p><strong>' . __( 'For more information:', 'footnotes-made-easy' ) . '</strong></p>';
		$help_text .= '<p><a href="https://wordpress.org/plugins/footnotes-made-easy/">' . __( 'Instructions', 'footnotes-made-easy' ) . '</a></p>';
		$help_text .= '<p><a href="https://wordpress.org/support/plugin/footnotes-made-easy">' . __( 'Support Forum', 'footnotes-made-easy' ) . '</a></p></h4>';

		return $help_text;
	}

	/**
	* Add to Admin
	*
	* Add the options page to the admin menu
	*
	* @since	1.0
	*/

	function add_options_page() {

		global $footnotes_hook;

		$footnotes_hook = add_options_page( __( 'Footnotes Made Easy', 'footnotes-made-easy' ), __( 'Footnotes', 'footnotes-made-easy' ), 'manage_options', __FILE__, array( $this, 'footnotes_options_page' ) );

		add_action( 'load-' . $footnotes_hook, array( $this, 'footnotes_help' ) );

	}

	/**
	* Insert additional CSS
	*
	* Add additional CSS to the page for the footnotes styling
	*
	* @since	1.0
	*/

	function insert_styles(){
		?>
		<style type="text/css">
			<?php if ( 'symbol' !== $this->current_options[ 'list_style_type' ] ): ?>
			ol.footnotes>li {list-style-type:<?php echo $this->current_options[ 'list_style_type' ]; ?>;}
			<?php endif; ?>
			<?php echo "ol.footnotes { color:#666666; }\nol.footnotes li { font-size:80%; }\n"; ?>
		</style>
		<?php
	}

	/**
	* Convert number
	*
	* Convert number to a specific style
	*
	* @since	1.0
	*
	* @param	$num	string	The number to be converted
	* @param	$style	string	The style of output required
	* @param	$total	string	The total length
	* @return			string 	The converted number
	*/

	function convert_num ( $num, $style, $total ){

		switch ( $style ) {
			case 'decimal-leading-zero' :
				$width = max( 2, strlen( $total ) );
				return sprintf( "%0{$width}d", $num );
			case 'lower-roman' :
				return $this->roman( $num, 'lower' );
			case 'upper-roman' :
				return $this->roman( $num );
			case 'lower-alpha' :
				return $this->alpha( $num, 'lower' );
			case 'upper-alpha' :
				return $this->alpha( $num );
			case 'symbol' :
				$sym = '';
				for ( $i = 0; $i < $num; $i++ ) {
					$sym .= $this->current_options[ 'list_style_symbol' ];
				}
				return $sym;
		}
	}

	/**
	* Convert to a roman numeral
	*
	* Convert a provided number into a roman numeral
	*
	* @since	1.0
	*
	* @param	int		$num	The number to convert.
	* @param	string	$case	Upper or lower case.
	* @return	string			The roman numeral
	*/

	function roman( $num, $case= 'upper' ){

		$num = ( int ) $num;
		$conversion = array(
							'M' => 1000,
							'CM' => 900,
							'D' => 500,
							'CD' => 400,
							'C' => 100,
							'XC' => 90,
							'L' => 50,
							'XL' => 40,
							'X' => 10,
							'IX' => 9,
							'V' => 5,
							'IV' => 4,
							'I' => 1
							);
		$roman = '';

		foreach ( $conversion as $r => $d ){
			$roman .= str_repeat( $r, ( int )( $num / $d ) );
			$num %= $d;
		}

		return ( $case === 'lower' ) ? strtolower( $roman ) : $roman;
	}

	function alpha( $num, $case='upper' ){
		$j = 1;
		for ( $i = 'A'; $i <= 'ZZ'; $i++ ){
			if ( $j === $num ){
				if ( 'lower' === $case )
					return strtolower( $i );
				else
					return $i;
			}
			$j++;
		}

	}

	/**
	* Tooltip Scripts
	*
	* Add scripts and CSS for pretty tooltips
	*
	* @since	1.0
	*/

	function tooltip_scripts() {

		wp_enqueue_script(
							'wp-footnotes-tooltips',
							plugins_url( 'js/tooltips.min.js' , __FILE__ ),
							array(
									'jquery',
									'jquery-ui-widget',
									'jquery-ui-tooltip',
									'jquery-ui-core',
									'jquery-ui-position'
								)
							);

		wp_enqueue_style( 'wp-footnotes-tt-style', plugins_url( 'css/tooltips.min.css' , __FILE__ ), array(), null );
	}
}
