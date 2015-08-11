<?php

/*
Plugin Name: Contact Form 7 - Postcode Extension
Plugin URI: https://github.com/mrhewitt/contact-form-7-postcode-extension
Description: Provides a postcode field that provides an address lookup against the http://www.postcodesoftware.net SDK.  Requires Contact Form 7
Version: 1.0
Author: Mark Hewitt
Author URI: http://www.markhewitt.co.za
License: GPL2
*/

/*  Copyright 2015  Mark Hewitt, http://www.markhewitt.co.za

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License, version 2, as 
    published by the Free Software Foundation.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/


add_action( 'plugins_loaded', 'wpcf7_postcode_init' , 20 );
function wpcf7_postcode_init(){
	add_action( 'wpcf7_init', 'wpcf7_postcode_add_shortcode_postcode' );
	add_filter( 'wpcf7_validate_postcode*', 'wpcf7_postcode_validation_filter', 10, 2 );
}



function wpcf7_postcode_add_shortcode_postcode() {
	wpcf7_add_shortcode( array( 'postcode'), 'wpcf7_postcode_shortcode_handler', true );
}
function wpcf7_postcode_shortcode_handler( $tag ) {
	$tag = new WPCF7_Shortcode( $tag );

	if ( empty( $tag->name ) ) {
		return '';
	}
	
	$validation_error = wpcf7_get_validation_error( $tag->name );
	$class = wpcf7_form_controls_class( $tag->type, 'wpcf7-text' );

	if ( $validation_error ) {
		$class .= ' wpcf7-not-valid';
	}
	
	$atts = array();

	$atts['class'] = $tag->get_class_option( $class );
	$atts['id'] = $tag->get_id_option();
	$atts['tabindex'] = $tag->get_option( 'tabindex', 'int', true );

	if ( $tag->is_required() ) {
		$atts['aria-required'] = 'true';
	}
	
	$atts['aria-invalid'] = $validation_error ? 'true' : 'false';

	$atts = wpcf7_format_atts( $atts );

	$html = sprintf(
		'<span style="display:block" class="wpcf7-form-control-wrap wpcf7-form-postcode-enabled">
			<input type="hidden" name="'.sanitize_html_class($tag->name).'" />
			<span style="display:block">Postcode*</span>
			<input type="text" name="wp7cf_postcode_code" maxlength="8" style="text-transform:uppercase;width:128px;margin-right:6px" />
			<button onclick="wp7cf_postcode_lookup(jQuery(this));return false;" style="display:inline-block;font-weight:bold;padding-bottom:2px;position: relative;top: -4px;left: -4px;" disabled class="">Lookup</button>
			<img class="ajax-loader" src="'.wpcf7_ajax_loader().'" alt="Checking..." style="display: none;">
			<span class="wpcf7-postcode-address" style="display:none">
				<span style="display:block" class="wp7cf-ostcode-choice-wrap">
					<span style="display:block">Select Address</span>
					<span style="display:block"><select name="wp7cf_postcode_premesis"></select></span>
				</span>
				<span style="display:block" class="wp7cf-postcode-address-wrap">
					<span style="display:block">Address Line 1</span>
					<span style="display:block"><input type="text" name="wp7cf_postcode_addr1" readonly /></span>
					<span style="display:block">Address Line 2</span>
					<span style="display:block"><input type="text" name="wp7cf_postcode_addr2" readonly /></span>
					<span style="display:block">Town</span>
					<span style="display:block"><input type="text" name="wp7cf_postcode_town" readonly /></span>' . /*
					<span style="display:block">County</span>
					<span style="display:block"><input type="text" name="wp7cf_postcode_county" readonly /></span>*/ '
				</span>
			</span>
		</span>',
		sanitize_html_class( $tag->name ), $atts, $validation_error );
	
	return $html;
}


/**
 * Load the javascript containing the client side handling for postcode fields and setup an ajax
 * action handler for processing the postcode lookup from the form
 */
wp_enqueue_script( 'postcode-ajax-script', plugins_url( 'postcode.js', __FILE__ ), array('jquery') );
wp_localize_script( 'postcode-ajax-script', 'postcode_object', array( 'ajax_url' => admin_url( 'admin-ajax.php' )) );
add_action( 'wp_ajax_wpcf7_postcode_lookup', 'wpcf7_postcode_lookup' );
add_action( 'wp_ajax_nopriv_wpcf7_postcode_lookup', 'wpcf7_postcode_lookup' );

function wpcf7_postcode_lookup() {
	// fetch the data from the postcode SDK
	@include_once( dirname(__FILE__).'/account.php' );
	if ( !isset($POSTCODE_ACCOUNT) ) {
		$POSTCODE_ACCOUNT = 'test';
		$POSTCODE_PASSWORD = 'test';
	}
	$data = file_get_contents('http://ws1.postcodesoftware.co.uk/lookup.asmx/getAddress?account='.$POSTCODE_ACCOUNT.'&password='.$POSTCODE_PASSWORD.'&postcode='.urlencode($_POST['postcode']));
	// parse the xml so we can do some processing on the address and convert to JSON
	$address = (array)simplexml_load_string($data);

	// if there is premise data, expand this into an array for easy processing in JS
	if ( !empty($address['PremiseData']) ) {
		$address['PremiseData'] = explode(';',trim($address['PremiseData'],';'));
		foreach ( $address['PremiseData'] as &$premise ) {
			if ( !empty($premise) ) { 
				$parts = explode('|',trim($premise,'|'));
				$final_premise = array();
				foreach ( $parts as $p ) {
					if ( !empty($p) ) {
						$final_premise[] = str_replace( array('/',' <br> ','|'), ', ', $p);
					}
				}
				// if the last piece of a premisis is not a street number, we append a , so it looks better
				// ie.. Flat 3, Priestley Court, Cornmill View   as opposed to 18 St George Street
				if ( !is_numeric(end($final_premise)) ) {
					$final_premise[key($final_premise)] .= ',';
				}
				$premise = join(', ',$final_premise);
			}
		}
	}
	
	// ensure there is a address2 in case this is empty from the API, just to simplyfy the js code
	if ( !isset($address['Address2']) ) {
		$address['Address2'] = '';
	}
	
	// give out API consumer a JSON block in response
	echo json_encode($address);
	wp_die();
}

//add_filter( 'wpcf7_validate_text', 'wpcf7_text_validation_filter', 10, 2 );  // in init
function wpcf7_postcode_validation_filter( $result, $tag ) {
	$tag = new WPCF7_Shortcode( $tag );

	$name = $tag->name;

	$value = isset( $_POST[$name] )
		? trim( wp_unslash( strtr( (string) $_POST[$name], "\n", " " ) ) )
		: '';

	// for now postcode is always required
	if ( /*$tag->is_required()*/true && '' == $value ) {
		$result->invalidate( $tag, wpcf7_get_message( 'invalid_required' ) );
	}

	return $result;
}




if ( is_admin() ) {
	add_action( 'admin_init', 'wpcf7_postcode_add_tag_generator_postcode', 25 );
}

function wpcf7_postcode_add_tag_generator_postcode() {
	$tag_generator = WPCF7_TagGenerator::get_instance();
	$tag_generator->add( 'postcode', __( 'postcode', 'contact-form-7' ),
		'wpcf7_postcode_tag_generator_postcode' );
}

function wpcf7_postcode_tag_generator_postcode( $contact_form , $args = '' ){
	$args = wp_parse_args( $args, array() );

	$description = __( "Generate a form-tag for a postcode lookup field.", 'contact-form-7' );
	$desc_link = "";
?>
<div class="control-box">
<fieldset>
<legend><?php echo sprintf( esc_html( $description ), $desc_link ); ?></legend>

<table class="form-table">
<tbody>

	<tr>
	<th scope="row"><label for="<?php echo esc_attr( $args['content'] . '-name' ); ?>"><?php echo esc_html( __( 'Name', 'contact-form-7' ) ); ?></label></th>
	<td><input type="text" name="name" class="tg-name oneline" id="<?php echo esc_attr( $args['content'] . '-name' ); ?>" /></td>
	</tr>

</tbody>
</table>
</fieldset>
</div>

<div class="insert-box">
	<input type="text" name="postcode" class="tag code" readonly="readonly" onfocus="this.select()" />

	<div class="submitbox">
	<input type="button" class="button button-primary insert-tag" value="<?php echo esc_attr( __( 'Insert Tag', 'contact-form-7' ) ); ?>" />
	</div>
</div>
<?php
}
