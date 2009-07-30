<?php

define ( 'BP_ACTIVITY_DB_VERSION', '1716' );

/* Define the slug for the component */
if ( !defined( 'BP_ACTIVITY_SLUG' ) )
	define ( 'BP_ACTIVITY_SLUG', 'activity' );

require ( BP_PLUGIN_DIR . '/bp-activity/bp-activity-classes.php' );
require ( BP_PLUGIN_DIR . '/bp-activity/bp-activity-templatetags.php' );
require ( BP_PLUGIN_DIR . '/bp-activity/bp-activity-widgets.php' );
require ( BP_PLUGIN_DIR . '/bp-activity/bp-activity-cssjs.php' );
require ( BP_PLUGIN_DIR . '/bp-activity/bp-activity-filters.php' );

function bp_activity_install() {
	global $wpdb, $bp;
	
	if ( !empty($wpdb->charset) )
		$charset_collate = "DEFAULT CHARACTER SET $wpdb->charset";
	
	$sql[] = "CREATE TABLE {$bp->activity->table_name} (
		  		id bigint(20) NOT NULL AUTO_INCREMENT PRIMARY KEY,
				user_id bigint(20) NOT NULL,
				component_name varchar(75) NOT NULL,
				component_action varchar(75) NOT NULL,
				content longtext NOT NULL,
				primary_link varchar(150) NOT NULL,
				item_id bigint(20) NOT NULL,
				secondary_item_id bigint(20) NOT NULL,
				date_recorded datetime NOT NULL,
				hide_sitewide bool DEFAULT 0,
				KEY date_recorded (date_recorded),
				KEY user_id (user_id),
				KEY item_id (item_id),
				KEY component_name (component_name)
		 	   ) {$charset_collate};";

	require_once( ABSPATH . 'wp-admin/upgrade-functions.php' );
	dbDelta($sql);
	
	/* Drop the old sitewide and user activity tables */
	$wpdb->query( "DROP TABLE {$wpdb->base_prefix}bp_activity_user_activity" );
	$wpdb->query( "DROP TABLE {$wpdb->base_prefix}bp_activity_sitewide" );
	
	update_site_option( 'bp-activity-db-version', BP_ACTIVITY_DB_VERSION );
}

function bp_activity_setup_globals() {
	global $bp, $wpdb, $current_blog;

	$bp->activity->table_name = $wpdb->base_prefix . 'bp_activity_user_activity_cached';
	$bp->activity->image_base = BP_PLUGIN_URL . '/bp-activity/images';
	$bp->activity->slug = BP_ACTIVITY_SLUG;

	if ( is_site_admin() && get_site_option( 'bp-activity-db-version' ) < BP_ACTIVITY_DB_VERSION  )
		bp_activity_install();
}
add_action( 'plugins_loaded', 'bp_activity_setup_globals', 5 );
add_action( 'admin_menu', 'bp_activity_setup_globals', 1 );

function bp_activity_setup_root_component() {
	/* Register 'activity' as a root component (for RSS feed use) */
	bp_core_add_root_component( BP_ACTIVITY_SLUG );
}
add_action( 'plugins_loaded', 'bp_activity_setup_root_component', 1 );

function bp_activity_setup_nav() {
	global $bp;

	/* Add 'Activity' to the main navigation */
	bp_core_new_nav_item( array( 'name' => __( 'Activity', 'buddypress' ), 'slug' => $bp->activity->slug, 'position' => 10, 'screen_function' => 'bp_activity_screen_my_activity', 'default_subnav_slug' => 'just-me' ) );

	$activity_link = $bp->loggedin_user->domain . $bp->activity->slug . '/';

	/* Add the subnav items to the activity nav item */
	bp_core_new_subnav_item( array( 'name' => __( 'Just Me', 'buddypress' ), 'slug' => 'just-me', 'parent_url' => $activity_link, 'parent_slug' => $bp->activity->slug, 'screen_function' => 'bp_activity_screen_my_activity', 'position' => 10 ) );
	bp_core_new_subnav_item( array( 'name' => __( 'My Friends', 'buddypress' ), 'slug' => 'my-friends', 'parent_url' => $activity_link, 'parent_slug' => $bp->activity->slug, 'screen_function' => 'bp_activity_screen_friends_activity', 'position' => 20, 'item_css_id' => 'activity-my-friends' ) );

	if ( $bp->current_component == $bp->activity->slug ) {
		if ( bp_is_home() ) {
			$bp->bp_options_title = __( 'My Activity', 'buddypress' );
		} else {
			$bp->bp_options_avatar = bp_core_get_avatar( $bp->displayed_user->id, 1 );
			$bp->bp_options_title = $bp->displayed_user->fullname; 
		}
	}

	do_action( 'bp_activity_setup_nav' );
}
add_action( 'wp', 'bp_activity_setup_nav', 2 );
add_action( 'admin_menu', 'bp_activity_setup_nav', 2 );


/********************************************************************************
 * Screen Functions
 *
 * Screen functions are the controllers of BuddyPress. They will execute when their
 * specific URL is caught. They will first save or manipulate data using business
 * functions, then pass on the user to a template file.
 */

function bp_activity_screen_my_activity() {
	do_action( 'bp_activity_screen_my_activity' );
	bp_core_load_template( apply_filters( 'bp_activity_template_my_activity', 'activity/just-me' ) );	
}

function bp_activity_screen_friends_activity() {
	do_action( 'bp_activity_screen_friends_activity' );
	bp_core_load_template( apply_filters( 'bp_activity_template_friends_activity', 'activity/my-friends' ) );	
}


/********************************************************************************
 * Action Functions
 *
 * Action functions are exactly the same as screen functions, however they do not
 * have a template screen associated with them. Usually they will send the user
 * back to the default screen after execution.
 */

function bp_activity_action_sitewide_feed() {
	global $bp, $wp_query;

	if ( $bp->current_component != $bp->activity->slug || $bp->current_action != 'feed' || $bp->displayed_user->id )
		return false;

	$wp_query->is_404 = false;
	status_header( 200 );

	include_once( 'bp-activity/feeds/bp-activity-sitewide-feed.php' );
	die;
}
add_action( 'wp', 'bp_activity_action_sitewide_feed', 3 );

function bp_activity_action_personal_feed() {
	global $bp, $wp_query;	

	if ( $bp->current_component != $bp->activity->slug || !$bp->displayed_user->id || $bp->current_action != 'feed' )
		return false;
	
	$wp_query->is_404 = false;
	status_header( 200 );

	include_once( 'bp-activity/feeds/bp-activity-personal-feed.php' );
	die;
}
add_action( 'wp', 'bp_activity_action_personal_feed', 3 );

function bp_activity_action_friends_feed() {
	global $bp, $wp_query;

	if ( $bp->current_component != $bp->activity->slug || !$bp->displayed_user->id || $bp->current_action != 'my-friends' || $bp->action_variables[0] != 'feed' )
		return false;

	$wp_query->is_404 = false;	
	status_header( 200 );

	include_once( 'bp-activity/feeds/bp-activity-friends-feed.php' );
	die;	
}
add_action( 'wp', 'bp_activity_action_friends_feed', 3 );


/********************************************************************************
 * Business Functions
 *
 * Business functions are where all the magic happens in BuddyPress. They will
 * handle the actual saving or manipulation of information. Usually they will
 * hand off to a database class for data access, then return
 * true or false on success or failure.
 */

function bp_activity_add( $args = '' ) {
	global $bp, $wpdb;
	
	$defaults = array(
		'user_id' => $bp->loggedin_user->id,
		'content' => false,
		'primary_link' => false,
		'component_name' => false,
		'component_action' => false,
		'item_id' => false,
		'secondary_item_id' => false,
		'recorded_time' => time(),
		'hide_sitewide' => false
	);

	$r = wp_parse_args( $args, $defaults );
	extract( $r, EXTR_SKIP );
	
	/* Insert the "time-since" placeholder */
	if ( $content ) {
		if ( !$pos = strpos( $content, '<blockquote' ) ) {
			if ( !$pos = strpos( $content, '<div' ) ) {
				if ( !$pos = strpos( $content, '<ul' ) ) {
					$content .= ' <span class="time-since">%s</span>';
				}
			}
		}
		
		if ( $pos ) {
			$before = substr( $content, 0, $pos );
			$after = substr( $content, $pos, strlen( $content ) );
			
			$content = $before . ' <span class="time-since">%s</span>' . $after;
		}
	}

	$activity = new BP_Activity_Activity;
	$activity->user_id = $user_id;
	$activity->content = $content;
	$activity->primary_link = $primary_link;
	$activity->component_name = $component_name;
	$activity->component_action = $component_action;
	$activity->item_id = $item_id;
	$activity->secondary_item_id = $secondary_item_id;
	$activity->date_recorded = $recorded_time;
	$activity->hide_sitewide = $hide_sitewide;

	if ( !$activity->save() )
		return false;

	do_action( 'bp_activity_add', $args );
	
	return true;
}

/* There are multiple ways to delete activity items, depending on the information you have at the time. */

function bp_activity_delete_by_item_id( $args = '' ) {
	global $bp;

	$defaults = array(
		'item_id' => false,
		'component_name' => false,
		'component_action' => false,
		'user_id' => false, // optional
		'secondary_item_id' => false // optional
	);

	$r = wp_parse_args( $args, $defaults );
	extract( $r, EXTR_SKIP );

	if ( !BP_Activity_Activity::delete_by_item_id( $item_id, $component_name, $component_action, $user_id, $secondary_item_id ) )
		return false;

	do_action( 'bp_activity_delete_by_item_id', $item_id, $component_name, $component_action, $user_id, $secondary_item_id );

	return true;
}

function bp_activity_delete_by_activity_id( $activity_id ) {
	if ( !BP_Activity_Activity::delete_by_activity_id( $activity_id ) )
		return false;

	do_action( 'bp_activity_delete_by_activity_id', $activity_id );

	return true;	
}

function bp_activity_delete_by_content( $user_id, $content, $component_name, $component_action ) {
	if ( !BP_Activity_Activity::delete_by_content( $user_id, $content, $component_name, $component_action ) )
		return false;

	do_action( 'bp_activity_delete_by_content', $user_id, $content, $component_name, $component_action );

	return true;
}

function bp_activity_delete_for_user_by_component( $user_id, $component_name ) {
	if ( !BP_Activity_Activity::delete_for_user_by_component( $user_id, $component_name ) )
		return false;
		
	do_action( 'bp_activity_delete_for_user_by_component', $user_id, $component_name );
	
	return true;
}

function bp_activity_get_last_updated() {
	return BP_Activity_Activity::get_last_updated();
}

function bp_activity_get_sitewide_activity( $max_items = 30, $pag_num = false, $pag_page = false, $filter = false ) {
	return BP_Activity_Activity::get_sitewide_activity( $max_items, $pag_num, $pag_page, $filter );
}

function bp_activity_get_user_activity( $user_id, $max_items = 30, $since = '-4 weeks', $pag_num = false, $pag_page = false, $filter = false ) {
	return BP_Activity_Activity::get_activity_for_user( $user_id, $max_items, $since, $pag_num, $pag_page, $filter );
}

function bp_activity_get_friends_activity( $user_id, $max_items = 30, $since = '-4 weeks', $max_items_per_friend = false, $pag_num = false, $pag_page = false, $filter = false ) {
	return BP_Activity_Activity::get_activity_for_friends( $user_id, $max_items, $since, $max_items_per_friend, $pag_num, $pag_page, $filter );
}

function bp_activity_remove_data( $user_id ) {
	// Clear the user's activity from the sitewide stream and clear their activity tables
	BP_Activity_Activity::delete_for_user( $user_id );
	
	do_action( 'bp_activity_remove_data', $user_id );
}
add_action( 'wpmu_delete_user', 'bp_activity_remove_data' );
add_action( 'delete_user', 'bp_activity_remove_data' );

/* Ordering function - don't call this directly */
function bp_activity_order_by_date( $a, $b ) {
	return strcasecmp( $b['date_recorded'], $a['date_recorded'] );	
}

/**** DEPRECATED FUNCTIONS (DO NOT USE IN YOUR CODE) **************/

/* DEPRECATED - use bp_activity_add() */
function bp_activity_record( $item_id, $component_name, $component_action, $is_private, $secondary_item_id = false, $user_id = false, $secondary_user_id = false, $recorded_time = false ) {
	global $bp, $wpdb;
	
	if ( !$user_id )
		$user_id = $bp->loggedin_user->id;

	if ( !$recorded_time )
		$recorded_time = time();
	
	$args = compact( 'user_id', 'content', 'component_name', 'component_action', 'item_id', 'secondary_item_id', 'recorded_time' );
	bp_activity_add( $args );
	
	if ( $secondary_user_id  ) {
		$hide_sitewide = true;
		$args = compact( 'user_id', 'content', 'component_name', 'component_action', 'item_id', 'secondary_item_id', 'recorded_time', 'hide_sitewide' );
		bp_activity_add( $args );
	}
	
	do_action( 'bp_activity_record', $item_id, $component_name, $component_action, $is_private, $secondary_item_id, $user_id, $secondary_user_id );
	
	return true;
}

/* DEPRECATED - use bp_activity_delete_by_item_id() */
function bp_activity_delete( $item_id, $component_name, $component_action, $user_id, $secondary_item_id ) {	
	if ( !bp_activity_delete_by_item_id( array( 'item_id' => $item_id, 'component_name' => $component_name, 'component_action' => $component_action, 'user_id' => $user_id, 'secondary_item_id' => $secondary_item_id ) ) )
		return false;
		
	do_action( 'bp_activity_delete', $item_id, $component_name, $component_action, $user_id, $secondary_item_id );
	
	return true;
}

?>