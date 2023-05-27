<?php
/**
 * Enqueue frontend JavaScript and CSS
 */
function pmpro_enqueue_scripts() {
    global $pmpro_level, $pmpro_pages;
    
    // Figure out which frontend.css file to load.    
    if( file_exists( get_stylesheet_directory() . "/paid-memberships-pro/css/frontend.css" ) ) {
        $frontend_css = get_stylesheet_directory_uri() . "/paid-memberships-pro/css/frontend.css";        
    } elseif( file_exists( get_template_directory() . "/paid-memberships-pro/frontend.css" ) ) {
        $frontend_css = get_template_directory_uri() . "/paid-memberships-pro/frontend.css";        
    } else {
        $frontend_css = plugins_url( 'css/frontend.css',dirname(__FILE__) );        
    }
    wp_enqueue_style( 'pmpro_frontend', $frontend_css, array(), PMPRO_VERSION, "screen" );
    
    // Figure out which frontend-rlt.css file to load if applicable.
    if( is_rtl() ) {        
        if( file_exists( get_stylesheet_directory() . "/paid-memberships-pro/css/frontend-rtl.css" ) ) {
            $frontend_css_rtl = get_stylesheet_directory_uri() . "/paid-memberships-pro/css/frontend-rtl.css";
        } elseif ( file_exists( get_template_directory() . "/paid-memberships-pro/css/frontend-rtl.css" ) ) {
            $frontend_css_rtl = get_template_directory_uri() . "/paid-memberships-pro/css/frontend-rtl.css";
        } else {
            $frontend_css_rtl = plugins_url('css/frontend-rtl.css',dirname(__FILE__) );
        }        
        wp_enqueue_style( 'pmpro_frontend_rtl', $frontend_css_rtl, array(), PMPRO_VERSION, "screen" ); 
    }

    // Print styles.
    if(file_exists(get_stylesheet_directory() . "/paid-memberships-pro/css/print.css"))
        $print_css = get_stylesheet_directory_uri() . "/paid-memberships-pro/css/print.css";
    elseif(file_exists(get_template_directory() . "/paid-memberships-pro/print.css"))
        $print_css = get_template_directory_uri() . "/paid-memberships-pro/print.css";
    else
        $print_css = plugins_url('css/print.css',dirname(__FILE__) );
    wp_enqueue_style('pmpro_print', $print_css, array(), PMPRO_VERSION, "print");
    
    // Checkout page JS
    if ( pmpro_is_checkout() ) {
        wp_register_script( 'pmpro_checkout',
                            plugins_url( 'js/pmpro-checkout.js', dirname(__FILE__) ),
                            array( 'jquery' ),
                            PMPRO_VERSION );

        wp_localize_script( 'pmpro_checkout', 'pmpro', array(
            'ajaxurl' => admin_url( 'admin-ajax.php' ),
            'ajax_timeout' => apply_filters( 'pmpro_ajax_timeout', 5000, 'applydiscountcode' ),
            'show_discount_code' => pmpro_show_discount_code(),
			'discount_code_passed_in' => !empty( $_REQUEST['discount_code'] ),
        ));
        wp_enqueue_script( 'pmpro_checkout' );
    }
    
    // Change Password page JS 
	$is_change_pass_page = ! empty( $pmpro_pages['member_profile_edit'] )
							&& is_page( $pmpro_pages['member_profile_edit'] )
							&& ! empty( $_REQUEST['view'] )
							&& $_REQUEST['view'] === 'change-password';
	$is_reset_pass_page = ! empty( $pmpro_pages['login'] )
							&& is_page( $pmpro_pages['login'] )
							&& ! empty( $_REQUEST['action'] )
							&& $_REQUEST['action'] === 'rp';
		
	if ( $is_change_pass_page || $is_reset_pass_page ) {
        wp_register_script( 'pmpro_login',
                            plugins_url( 'js/pmpro-login.js', dirname(__FILE__) ),
                            array( 'jquery', 'password-strength-meter' ),
                            PMPRO_VERSION );

        /**
         * Filter to allow weak passwords on the 
         * change password and reset password forms.
         * At this time, this only disables the JS check on the frontend.
         * There is no backend check for weak passwords on those forms.
         * 
         * @since 2.3.3
         *
         * @param bool $allow_weak_passwords    Whether to allow weak passwords.
         */
        $allow_weak_passwords = apply_filters( 'pmpro_allow_weak_passwords', false );

        wp_localize_script( 'pmpro_login', 'pmpro', array(
            'pmpro_login_page' => 'changepassword',
			'strength_indicator_text' => __( 'Strength Indicator', 'paid-memberships-pro' ),
            'allow_weak_passwords' => $allow_weak_passwords ) );
        wp_enqueue_script( 'pmpro_login' );	
    }

    // Enqueue select2 on front end and user profiles
	if( pmpro_is_checkout() || 
        ! empty( $_REQUEST['level'] ) ||
        ! empty( $pmpro_level ) ||
		( class_exists("Theme_My_Login") && method_exists('Theme_My_Login', 'is_tml_page') && Theme_My_Login::is_tml_page("profile") ) ||
		( isset( $pmpro_pages['member_profile_edit'] ) && is_page( $pmpro_pages['member_profile_edit'] ) ) ) {
		wp_enqueue_style( 'select2', plugins_url('css/select2.min.css', dirname(__FILE__)), '', '4.0.3', 'screen' );
		wp_enqueue_script( 'select2', plugins_url('js/select2.min.js', dirname(__FILE__)), array( 'jquery' ), '4.0.3' );
	}
}
add_action( 'wp_enqueue_scripts', 'pmpro_enqueue_scripts' );

/**
 * Enqueue admin JavaScript and CSS
 */
function pmpro_admin_enqueue_scripts() {
    // Enqueue Select2.  
    wp_register_script( 'select2',
                        plugins_url( 'js/select2.min.js', dirname(__FILE__) ),
                        array( 'jquery', 'jquery-ui-sortable' ),
                        '4.0.3' );
    wp_enqueue_style( 'select2', plugins_url('css/select2.min.css', dirname(__FILE__)), '', '4.0.3', 'screen' );
    wp_enqueue_script( 'select2' );


    if ( ( isset( $_REQUEST['page'] ) && $_REQUEST['page'] == 'pmpro-wizard' ) && ( isset( $_REQUEST['step'] ) && $_REQUEST['step'] == 'done' ) ) {
        wp_register_script( 'pmpro_confetti', plugins_url( 'js/pmpro-confetti.js', __DIR__ ), [
                'jquery',
            ], PMPRO_VERSION );

        wp_enqueue_script( 'pmpro_confetti' );
    }
   

	$all_levels                  = pmpro_getAllLevels( true, true );
	$all_level_values_and_labels = [];

    // Enqueue pmpro-admin.js.
    wp_register_script( 'pmpro_admin',
                        plugins_url( 'js/pmpro-admin.js', dirname(__FILE__) ),
                        array( 'jquery', 'jquery-ui-sortable', 'select2' ),
                        PMPRO_VERSION );
    $all_levels = pmpro_getAllLevels( true, true );
    $all_level_values_and_labels = array();
    foreach( $all_levels as $level ) {
        $all_level_values_and_labels[] = array( 'value' => $level->id, 'label' => $level->name );
    }
    // Get HTML for empty field group.
    ob_start();
    pmpro_get_field_group_html();
    $empty_field_group_html = ob_get_clean();
    // Get HTML for empty field.
    ob_start();
    pmpro_get_field_html();
    $empty_field_html = ob_get_clean();

    wp_localize_script( 'pmpro_admin', 'pmpro', array(
        'all_levels' => $all_levels,
        'all_level_values_and_labels' => $all_level_values_and_labels,
        'user_fields_blank_group' => $empty_field_group_html,
        'user_fields_blank_field' => $empty_field_html,
        // We want the core WP translation so we can check for it in JS.
        'plugin_updated_successfully_text' => __( 'Plugin updated successfully.' ),
    ));
    wp_enqueue_script( 'pmpro_admin' );

    // Enqueue styles.
	// Figure out which admin.css to load.
	if ( file_exists( get_stylesheet_directory() . '/paid-memberships-pro/css/admin.css' ) ) {
		$admin_css = get_stylesheet_directory_uri() . '/paid-memberships-pro/css/admin.css';
	} elseif ( file_exists( get_template_directory() . '/paid-memberships-pro/admin.css' ) ) {
		$admin_css = get_template_directory_uri() . '/paid-memberships-pro/admin.css';
	} else {
		$admin_css = plugins_url( 'css/admin.css', __DIR__ );
	}
    
    // Figure out which admin-rtl.css to load if applicable.
    if ( file_exists( get_stylesheet_directory() . '/paid-memberships-pro/css/admin-rtl.css' ) ) {
		$admin_css_rtl = get_stylesheet_directory_uri() . '/paid-memberships-pro/css/admin-rtl.css';
	} elseif( file_exists( get_template_directory() . '/paid-memberships-pro/css/admin-rtl.css' ) ) {
		$admin_css_rtl = get_template_directory_uri() . '/paid-memberships-pro/css/admin-rtl.css';
	} else {
		$admin_css_rtl = plugins_url( 'css/admin-rtl.css', __DIR__ );
	}        

	wp_register_style( 'pmpro_admin', $admin_css, [], PMPRO_VERSION, 'screen' );
	wp_register_style( 'pmpro_admin_rtl', $admin_css_rtl, [], PMPRO_VERSION, 'screen' );	

	wp_enqueue_style( 'pmpro_admin' );

	if ( is_rtl() ) {
		wp_enqueue_style( 'pmpro_admin_rtl' );
	}
}
add_action( 'admin_enqueue_scripts', 'pmpro_admin_enqueue_scripts' );