<?php

/* ---------------------------------------------------------------------------
 * Child Theme URI | DO NOT CHANGE
 * --------------------------------------------------------------------------- */
define( 'CHILD_THEME_URI', get_stylesheet_directory_uri() );


/* ---------------------------------------------------------------------------
 * Define | YOU CAN CHANGE THESE
 * --------------------------------------------------------------------------- */

// White Label --------------------------------------------
define( 'WHITE_LABEL', false );

// Static CSS is placed in Child Theme directory ----------
define( 'STATIC_IN_CHILD', false );


/* ---------------------------------------------------------------------------
 * Enqueue Style
 * --------------------------------------------------------------------------- */
add_action( 'wp_enqueue_scripts', 'mfnch_enqueue_styles', 101 );

function mfnch_enqueue_styles() {

	// Enqueue the parent stylesheet
	// 	wp_enqueue_style( 'parent-style', get_template_directory_uri() .'/style.css' );		//we don't need this if it's empty

	// Enqueue the parent rtl stylesheet
	if ( is_rtl() ) {
		wp_enqueue_style( 'mfn-rtl', get_template_directory_uri() . '/rtl.css' );
	}

	// Enqueue the child stylesheet
	wp_dequeue_style( 'style' );
	wp_enqueue_style( 'style', get_stylesheet_directory_uri() . '/style.css' );

		// ANDREW -- Enqueue my child stylesheets
	wp_dequeue_style( 'customFonts' );
	wp_enqueue_style( 'customFonts', get_stylesheet_directory_uri() . '/css/customFonts.css' );
	wp_dequeue_style( 'customStyle' );
	wp_enqueue_style( 'customStyle', get_stylesheet_directory_uri() . '/css/customStyle.css' );
	
	/**
	wp_dequeue_style( 'defaultStyles' );
	wp_enqueue_style( 'defaultStyles', get_stylesheet_directory_uri() . '/css/defaultStyles.css' );
	**/
}


/* ---------------------------------------------------------------------------
 * Load Textdomain
 * --------------------------------------------------------------------------- */
add_action( 'after_setup_theme', 'mfnch_textdomain' );

function mfnch_textdomain() {
	load_child_theme_textdomain( 'betheme', get_stylesheet_directory() . '/languages' );
	load_child_theme_textdomain( 'mfn-opts', get_stylesheet_directory() . '/languages' );
}


/* ---------------------------------------------------------------------------
 * Override theme functions
 * 
 * if you want to override theme functions use the example below
 * --------------------------------------------------------------------------- */
// require_once( get_stylesheet_directory() .'/includes/content-portfolio.php' );

// Script loader for ad

function adToggle() {
	wp_enqueue_script( 'adToggle', get_stylesheet_directory_uri() . '/js/adToggle.js' ); // array('jQuery'), 1.8, false);
}

add_action( 'wp_enqueue_scripts', 'adToggle' );

/*********************ANDREW*******************/
function cleanSqlString( $string ) {

	//echo 'function cleanSqlString<BR>';
	$cleanString = str_replace( array( '\\', "\0", "\n", "\r", "'", '"', "\x1a" ), array( '\\\\', '\\0', '\\n', '\\r', "\\'", '\\"', '\\Z' ), $string );

	return $cleanString;
}

function gmdate_to_mydate( $gmdate, $timezone ) {
	/* $gmdate must be in YYYY-mm-dd H:i:s format*/
	$userTimezone = new DateTimeZone( $timezone );
	$gmtTimezone = new DateTimeZone( 'GMT' );
	$myDateTime = new DateTime( $gmdate, $gmtTimezone );
	$offset = $userTimezone->getOffset( $myDateTime );
	return date( "M j, h:i A", strtotime( $gmdate ) + $offset );
}

function get_timezone_abbreviation( $timezone_id ) {
	if ( $timezone_id ) {
		$abb_list = timezone_abbreviations_list();

		$abb_array = array();
		foreach ( $abb_list as $abb_key => $abb_val ) {
			foreach ( $abb_val as $key => $value ) {
				$value[ 'abb' ] = $abb_key;
				array_push( $abb_array, $value );
			}
		}

		foreach ( $abb_array as $key => $value ) {
			if ( $value[ 'timezone_id' ] == $timezone_id ) {
				return strtoupper( $value[ 'abb' ] );
			}
		}
	}
	return FALSE;
}

// function that runs when shortcode is called
function CENEventsFunction( $atts = [] ) {

	$atts = array_change_key_case( ( array )$atts, CASE_LOWER );

	$year = 2020;

	// override default attributes with user attributes
	$year = ( int )$atts[ "year" ];

	global $wpdb;
	//echo "CENEventsFunction()<BR>";

	
	$sql = "SELECT
    EPost.`ID`,
    EPost.`post_content`,
    EPost.`post_title`,
       EDetails.`start`,
       EDetails.`end`,
       EDetails.`timezone_name`,
       EDetails.`allday`,
       EDetails.`venue`,
       EDetails.`address`,
       EDetails.`contact_name`,
       EDetails.`contact_phone`,
       EDetails.`contact_email`,
       EDetails.`contact_url`,
       EDetails.`ticket_url`,
       thumbs.guid
FROM
    `wp_posts` EPost
    INNER JOIN `wp_ai1ec_events` EDetails 
			ON EPost.ID = EDetails.post_id
    LEFT OUTER JOIN `wp_postmeta` meta  
			ON meta.`meta_key`='_thumbnail_id' and meta.post_id=EDetails.post_id
    LEFT OUTER JOIN wp_posts thumbs
			ON thumbs.ID=meta.meta_value
    WHERE EPost.post_status = 'publish' 
			AND FROM_UNIXTIME(EDetails.end) > CURDATE() 
			AND YEAR(FROM_UNIXTIME(EDetails.start)) = " . $year . "
ORDER BY EDetails.`start` ASC";

	//	echo $sql . "<BR>";

	$DBRows = $wpdb->get_results( $sql, ARRAY_A );

	//print_r( $DBRows );

	$output = '<div class="eventYearDiv"><h2>' . $year . '</h2></div>
	<ul class="upcomingEvents">';


	foreach ( $DBRows as $myrows ) {
		$post_title = $myrows[ 'post_title' ];
		$post_content = $myrows[ "post_content" ];

		$timezone_name = get_timezone_abbreviation( $myrows[ "timezone_name" ] );


		$startDate = gmdate( "M j", $myrows[ "start" ] );
		$endDate = gmdate( "M j", $myrows[ "end" ] );
		
	$eventMonth = gmdate( "M", $myrows[ "start" ] );

if( $myrows[ "allday" ]== 1){
	$startDateEndDate = '<span class="startDateEndDate">' . $eventMonth . ' TBD 
			</span>';
} else{
		if ( $startDate == $endDate ) {
			$startDateEndDate = '<span class="startDateEndDate">' . $startDate . ' 
			</span>';
		} else {
			$startDateEndDate = '<span class="startDateEndDate">' . $startDate . ' - ' . $endDate . '</span>';
		}
}

		$timezone_name = $myrows[ "timezone_name" ];
		$year = gmdate( "Y", $myrows[ "start" ] );

		$venue = $myrows[ "venue" ];
		$address = $myrows[ "address" ];
		$contact_name = $myrows[ "contact_name" ];
		$contact_phone = $myrows[ "contact_phone" ];
		$contact_email = $myrows[ "contact_email" ];
		$contact_url = $myrows[ "contact_url" ];
		$ticket_url = $myrows[ "ticket_url" ];
		$guid = $myrows[ "guid" ];


		$output .= '
	<li class="eventRow">
	<div class="eventSquare">
		<div class="dateTime">' . $startDateEndDate . ' 
		</div>';

		if ( !empty( $guid ) ) {
			$output .= '	<div class="eventIcon"><img src="' . $guid . '"></div>';
		}

		$output .= '	
	</div>
	<div class="eventInfo">';

		if ( !empty( $ticket_url ) ) {
			$output .= '<div class="eventName"><a href="' . $ticket_url . '" target="_blank">' . $post_title . '</a></div>';
		} else {
			$output .= '<div class="eventName">' . $post_title . '</div>';
		}

		if ( !empty( $venue ) && !empty( $address ) ) {
			$output .= '<div class="eventLocation">
						<strong>Location: </strong> ' . $venue . ' (' . $address . ')
			</div>';
		} elseif ( empty( $venue ) && !empty( $address ) ) {
			$output .= '<div class="eventLocation">
						<strong>Location: </strong> ' . $address . '
			</div>';
		}

		if ( !empty( $ticket_url ) ) {
			$output .= '<div class="eventWebsite"><strong>Website: </strong> <a href="' . $ticket_url . '" target="_blank">' . $ticket_url . '</a> </div>';
		}

		if ( !empty( $post_content ) ) {
			$output .= '<div class="eventDescription"><strong>Description: </strong>' . $post_content . '</div>';
		}

		$output .= '</div>
	</li>
	';

	}

	$output .= '
	</ul>';

	echo $output;
}

// register shortcode
add_shortcode( 'CENEvents', 'CENEventsFunction' );

/**************** add_sign_up_link *************/

add_filter( 'wp_nav_menu_items', 'add_sign_up_link', 10, 2 );

function add_sign_up_link( $items, $args ) {
	global $wp_query;
	$postid = $wp_query->post->ID;
	$reg_link = get_post_meta( $postid, 'cen-partner-reg', true );
	$menuArray = $args->menu;
	$menuSlug = $menuArray->slug;
	
	if ( !empty( $reg_link) && $menuSlug=='main-menu-new') {
		$homelink = '<!-- $menuSlug=' . $menuSlug . ' --><li id="signUpLink" class="menu-item menu-item-type-custom menu-item-object-custom"><a href="' . $reg_link . '" target="_blank"><span>SIGN-UP</span></a></li>';
	}

	$items = $items . $homelink;

	return $items;
}