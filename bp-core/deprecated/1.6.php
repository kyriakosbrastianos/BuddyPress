<?php

/**
 * Deprecated Functions
 *
 * @package BuddyPress
 * @subpackage Core
 * @deprecated Since 1.6
 */

/** Toolbar functions *********************************************************/

function bp_admin_bar_remove_wp_menus() {
	_deprecated_function( __FUNCTION__, '1.6' );
}

function bp_admin_bar_root_site() {
	_deprecated_function( __FUNCTION__, '1.6' );
}

function bp_admin_bar_my_sites_menu() {
	_deprecated_function( __FUNCTION__, '1.6' );
}

function bp_admin_bar_comments_menu( $wp_admin_bar ) {
	_deprecated_function( __FUNCTION__, '1.6' );
}

function bp_admin_bar_appearance_menu() {
	_deprecated_function( __FUNCTION__, '1.6' );
}

function bp_admin_bar_updates_menu() {
	_deprecated_function( __FUNCTION__, '1.6' );
}

function bp_members_admin_bar_my_account_logout() {
	_deprecated_function( __FUNCTION__, '1.6' );
}

function bp_core_is_user_deleted( $user_id = 0 ) {
	_deprecated_function( __FUNCTION__, '1.6' );
	bp_is_user_deleted( $user_id );
}

function bp_core_is_user_spammer( $user_id = 0 ) {
	_deprecated_function( __FUNCTION__, '1.6' );
	bp_is_user_spammer( $user_id );
}


/**
 * Blogs functions
 */

/*
 * @deprecated 1.6
 * @deprecated No longer used; see bp_blogs_transition_activity_status()
 */
function bp_blogs_manage_comment( $comment_id, $comment_status ) {
	_deprecated_function( __FUNCTION__, '1.6', 'No longer used' );
}

/**
 * Members functions
 */

/**
 * @deprecated 1.6
 * @deprecated No longer used. Check for $bp->pages->activate->slug instead.
 */
function bp_has_custom_activation_page() {
	_deprecated_function( __FUNCTION__, '1.6' );
}

/**
 * Activity functions
 */

/**
 * @deprecated 1.6
 * @deprecated No longer used. Renamed to bp_activity_register_activity_actions().
 */
function updates_register_activity_actions() {
	_deprecated_function( __FUNCTION__, '1.6' );
}
?>