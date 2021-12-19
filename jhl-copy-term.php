<?php
/**
 * Plugin Name: Copy term
 * Description: Copy a term and its children
 * Version: 1.0.0
 * Author: Jason Lawton <jason@jasonlawton.com>
 */

include( 'inc/ajax.php' );

add_action( 'admin_menu', 'jhl_dt_add_admin_menu' );

function jhl_dt_add_admin_menu() {
    add_menu_page( 'Copy terms', 'Copy terms', 'manage_categories', 'copy_term', 'jhl_dt_options_page', 'dashicons-images-alt' );
}

function jhl_dt_options_page() {
    include 'options-form.php';
}
