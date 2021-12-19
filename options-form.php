<?php
wp_localize_script( 'my_ajax_script', 'myAjax', [ 'ajaxurl' => admin_url( 'admin-ajax.php' ) ] );
wp_enqueue_script( 'my_ajax_script' );

$taxonomies = get_taxonomies( [], 'objects' );
$taxonomies = array_values($taxonomies);
usort( $taxonomies, function( $a, $b ) {
    return strcasecmp( $a->label, $b->label );
});
// MCS::log( $taxonomies[0] );
?>

<h1>Copy taxonomy term (and children)</h1>

<style>
    form fieldset {
        border: 1px solid #aaa;
        display: inline-block;
    }
    form fieldset legend {
        margin-left: 10px;
        font-weight: bold;
        padding: 0 5px;
    }
    form fieldset > div {
        margin: 5px;
    }
    form fieldset table td {
        vertical-align: top;
    }
    form.jhl-cc > div {
        padding-bottom:15px;
    }
    form.jhl-cc label {
        display: inline-block;
        width: 230px;
    }
    .columns {
        display: flex;
    }
    .columns button {
        white-space: nowrap;
        margin-right: 5px;
    }
    .progress-wrapper {
        background-color: #ccc;
        border: 1px solid #999;
        height: 20px;
        width: 100%;
    }
    .progress-bar {
        width: 0%;
        background-color: #999;
        height:20px;
    }
</style>

<form action="" data-form="jhl-cc" class="jhl-cc" method="POST">
    <div>
        <fieldset>
            <legend>Taxonomies</legend>
            <div>
                <!-- step 1, select a taxonomy -->
                <table id="step-1">
                    <tr>
                        <td>
                            <label for="jhl_ct_taxonomy"><strong>Choose a taxonomy to show the terms of</strong></label>
                        </td>
                        <td>
                            <select name="taxonomy" id="jhl_ct_taxonomy">
                                <option value="">Select taxonomy</option>
                                <?php foreach( $taxonomies as $taxonomy ) { ?>
                                    <option value="<?php echo $taxonomy->name; ?>"><?php echo $taxonomy->label; ?> (<?php echo $taxonomy->name; ?>)</option>
                                <?php } ?>
                            </select>
                        </td>
                    </tr>
                </table>
                <!-- step 2, select a term from that taxonomy to duplicate -->
                <table id="step-2" style="display:none;">
                    <tr>
                        <td>
                            <label for="jhl_ct_term_parent"><strong>Which select a term you'd like to copy</strong></label>
                        </td>
                        <td>
                            <select name="term_parent" id="jhl_ct_term_parent">
                                <option value="">Select term</option>
                            </select>
                        </td>
                    </tr>
                </table>
                <!-- step 3, what is the name of the new term -->
                <table id="step-3" style="display:none;">
                    <tr>
                        <td>
                            <label for="jhl_ct_new_term_parent"><strong>What is the name of the new term</strong></label>
                        </td>
                        <td>
                            <input type="text" name="new_term_parent" id="jhl_ct_new_term_parent">
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <strong>Optionally, select what children<br>you would like to copy over to the<br>new term</strong>
                        </td>
                        <td>
                            <label for="toggle_all"><input type="checkbox" id="toggle_all">Toggle all</label>
                            <hr>
                            <div id="jhl_ct_new_term_children"></div>
                        </td>
                    </tr>
                    <tr>
                        <td></td>
                        <td><button id="copy_term">Copy</button></td>
                    </tr>
                </table>
            </div>
        </fieldset>
    </div>
</form>

<script>
var term_parents = [];
var term_children = [];
var term_parent_id = 0;
var total_ids = 0;
var taxonomy;
var term_id;

jQuery( document ).ready( function() {
    // step 1
    jQuery( '[data-form="jhl-cc"] select#jhl_ct_taxonomy' ).on( 'change', function( e ) {
        term_parents = [];
        taxonomy = jQuery( this ).val();
        if ( taxonomy ) {
            jQuery.ajax( {
                type : "POST",
                data : {
                    action: "get_term_parents",
                    taxonomy: taxonomy,
                },
                url : ajaxurl,
                success: function( data ) {
                    console.log( data );
                    jQuery( '#step-2' ).show();
                    // add options to the select box
                    data.data.forEach( elm => {
                        jQuery( '#jhl_ct_term_parent' ).append( `<option value="${elm.term_id}">${elm.name}</option>` );
                    } );
                }
            } );
        }
    } );

    // step 2
    jQuery( '[data-form="jhl-cc"] select#jhl_ct_term_parent' ).on( 'change', function( e ) {
        term_id = jQuery( this ).val();
        if ( term_id ) {
            jQuery.ajax( {
                type : "POST",
                data : {
                    action: "get_term_children",
                    taxonomy: taxonomy,
                    term_id: term_id,
                },
                url : ajaxurl,
                success: function( data ) {
                    console.log( data.data );
                    jQuery( '#step-3' ).show();
                    // add options to the div
                    jQuery( '#jhl_ct_new_term_children' ).empty();
                    data.data.forEach( elm => {
                        jQuery( '#jhl_ct_new_term_children' ).append( `<div><label><input type="checkbox" name="jhl_ct_new_term_child" value="${elm.name}">${elm.name}</label></div>` );
                    } );
                }
            } );
        }
    } );

    jQuery( '#toggle_all' ).on( 'change', function( e ) {
        var checked = jQuery( this ).is( ':checked' );
        jQuery( '#jhl_ct_new_term_children input' ).prop("checked", checked);
    } );

    jQuery( '#copy_term' ).on( 'click', function( e ) {
        e.preventDefault();
        // get form values
        var new_parent_term_value = jQuery( '#jhl_ct_new_term_parent' ).val();
        
        jQuery( '#jhl_ct_new_term_children input[type=checkbox]:checked').each( function( index ) { term_children.push(jQuery( this ).val() ) } );
        
        var submit_data = {
            action  : "add_term",
            taxonomy: taxonomy,
            term    : new_parent_term_value,
            parent  : 0
        };
        // init progress bar
        // initProgressBar();
        // submit new parent term admin_ajax
        jQuery.ajax( {
            type   : "POST",
            data   : submit_data,
            url    : ajaxurl,
            success: function( data ) {
                console.log( data );
                // update the progress bar

                // loop over the children, adding those
                processChildren( term_children.shift(), taxonomy, data.data.term_id );
            }
        } );
        // update progress bar

        // start adding child terms

        // update progress bar

        // on success clear form
    })

} );

var initProgressBar = function() {
    jQuery( '[data-widget="progress-bar"]' ).css( 'width', '0%' );
}

var processChildren = function( term, taxonomy, parent_id ) {
    if ( term == null ) {
        return;
    }
    jQuery.ajax( {
        type: "POST",
        data: {
            action  : "add_term",
            taxonomy: taxonomy,
            term    : term,
            parent  : parent_id,
        },
        url: ajaxurl,
        success: function( data ) {
            console.log( data );
            // updateProgressBar();
            if ( data.success == true ) {
                processChildren( term_children.shift(), taxonomy, parent_id );
            } else {
                return;
            }
        }
    } );
}

var updateProgressBar = function() {
    var width = ( ( total_ids - post_ids.length ) / total_ids ) * 100;
    console.log( width );
    jQuery( '[data-widget="progress-bar"]' ).css( 'width', width + '%' );
}
</script>
