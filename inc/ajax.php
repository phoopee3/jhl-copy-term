<?php
// ajax calls for doing stuff via jquery calls
// methods:
// get_term_parents - get the terms from the passed taxonomy id that have parent=0

// get list of taxonomies
add_action( 'wp_ajax_get_term_parents', 'jhl_ct_get_term_parents' );
add_action( 'wp_ajax_nopriv_get_term_parents', 'jhl_ct_get_term_parents' );

function jhl_ct_get_term_parents() {
    $taxonomy = sanitize_text_field( $_POST['taxonomy'] );
    header( 'Content-Type:application/json' );
    if ( $taxonomy ) {
        $terms = get_terms( [
            'taxonomy' => $taxonomy,
            'parent'   => 0,
            'hide_empty' => false,
        ] );

        if ( count( $terms ) == 0 ) {
            $terms = [];
        }
        echo json_encode( [
            'success' => true,
            'data' => array_values( $terms ),
        ] );
    } else {
        echo json_encode( [
            'success' => false,
            'message' => 'Invalid taxonomy',
            'data'    => [],
        ] );
    }
    die;
}

// get list of children
add_action( 'wp_ajax_get_term_children', 'jhl_ct_get_term_children' );
add_action( 'wp_ajax_nopriv_get_term_children', 'jhl_ct_get_term_children' );

function jhl_ct_get_term_children() {
    $taxonomy = sanitize_text_field( $_POST['taxonomy'] );
    $term_id = sanitize_text_field( $_POST['term_id'] );
    header( 'Content-Type:application/json' );
    if ( $term_id ) {
        $terms = get_terms( [
            'taxonomy' => $taxonomy,
            'parent'   => $term_id,
            'hide_empty' => false,
        ] );

        if ( count( $terms ) == 0 ) {
            $terms = [];
        }
        echo json_encode( [
            'success' => true,
            'data' => array_values( $terms ),
        ] );
    } else {
        echo json_encode( [
            'success' => false,
            'message' => 'Invalid term',
            'data'    => [],
        ] );
    }
    die;
}

// process to add term
add_action( 'wp_ajax_add_term', 'jhl_ct_add_term' );
add_action( 'wp_ajax_nopriv_add_term', 'jhl_ct_add_term' );

function jhl_ct_add_term() {
    $taxonomy = sanitize_text_field( $_POST['taxonomy'] );
    $term     = sanitize_text_field( $_POST['term'] );
    $parent   = sanitize_text_field( $_POST['parent'] );

    header( 'Content-Type:application/json' );
    if ( $taxonomy && $term ) {
        $term = wp_insert_term($term, $taxonomy, [
            'parent' => $parent
        ]);

        echo json_encode( [
            'success' => true,
            'data' => $term,
        ] );
    } else {
        echo json_encode( [
            'success' => false,
            'message' => 'Invalid data',
            'data'    => [],
        ] );
    }
    die;
}


// process to duplicate the children

