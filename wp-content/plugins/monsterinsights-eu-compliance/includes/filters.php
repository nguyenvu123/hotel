<?php
// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

function monsterinsights_eu_compliance_require_optin() {
    if ( ( function_exists( 'cookiebot_active' ) && cookiebot_active() ) || class_exists( 'Cookie_Notice' ) ) {
        return apply_filters( 'monsterinsights_eu_compliance_require_optin', false );
    }
    return false;
}

// Allow for AMP to wait for AMP consent
function monsterinsights_amp_add_analytics_consent( $analytics ) {
    $consent = monsterinsights_get_option( 'require_amp_consent', false ) ;
    if ( $consent ) {
        $analytics['monsterinsights-googleanalytics']['attributes']['data-block-on-consent'] = apply_filters( 'monsterinsights_amp_add_analytics_consent', "_till_accepted" ); // see https://www.ampproject.org/docs/reference/components/amp-consent#basic-blocking-behaviors for valid options
    }
    return $analytics;
}
add_filter( 'monsterinsights_amp_add_analytics', 'monsterinsights_amp_add_analytics_consent' );

// override demographics to false
add_filter( 'monsterinsights_get_option_demographics', 'monsterinsights_eu_compliance_addon_option_false' );

// override anonymize_ips to true
add_filter( 'monsterinsights_get_option_anonymize_ips', 'monsterinsights_eu_compliance_addon_option_true' );

// override gatracker_compatibility_mode to true
add_filter( 'monsterinsights_get_option_gatracker_compatibility_mode', '__return_true' );

// override userID to false
add_filter( 'monsterinsights_get_option_userid', 'monsterinsights_eu_compliance_addon_option_false' );

function monsterinsights_eu_compliance_addon_option_false( $value ){
    if ( monsterinsights_eu_compliance_require_optin() ) {
        return $value;
    }
    return false;
}

function monsterinsights_eu_compliance_addon_option_true( $value ){
    if ( monsterinsights_eu_compliance_require_optin() ) {
        return $value;
    }
    return true;
}

// Force DisplayFeatures off, even if they are turned on in the GA settings (override account settings)
function monsterinsights_eu_compliance_force_displayfeatures_off( $options ) {
    if ( monsterinsights_eu_compliance_require_optin() ) {
        return $options;
    }
    $options['demographics'] = "'set', 'displayFeaturesTask', null";
    return $options;
}
add_filter( 'monsterinsights_frontend_tracking_options_analytics_before_pageview', 'monsterinsights_eu_compliance_force_displayfeatures_off' );

// Hide userID and author custom dimension
function monsterinsights_eu_compliance_custom_dimensions( $dimensions ) {
    if ( monsterinsights_eu_compliance_require_optin() ) {
        return $dimensions;
    }
    $dimensions['user_id']['enabled'] = false;
    $dimensions['author']['enabled']  = false;
    return $dimensions;
}
add_filter( 'monsterinsights_available_custom_dimensions', 'monsterinsights_eu_compliance_custom_dimensions' );

// Remove user_id and author from being used even if already set to be used
function monsterinsights_eu_compliance_custom_dimensions_option( $dimensions ) {
    if ( monsterinsights_eu_compliance_require_optin() ) {
        return $dimensions;
    }
     if ( ! empty( $dimensions ) && is_array( $dimensions ) ) {
        foreach ( $dimensions as $key => $row ) {
            if ( ! empty( $row['type'] ) && ( $row['type'] === 'user_id' || $row['type'] === 'author' ) ) {
                unset( $dimensions[$key] );
            }
        }
    }
    return $dimensions;
}
add_filter( 'monsterinsights_get_option_custom_dimensions', 'monsterinsights_eu_compliance_custom_dimensions_option' );

// filter IPs in the Measurement Protocol calls
function monsterinsights_eu_compliance_mp_api_call_ip( $ip ) {
    if ( monsterinsights_eu_compliance_require_optin() ) {
        return $ip;
    }
    /**
        SRC: https://stackoverflow.com/questions/48767382/anonymize-ipv4-and-ipv6-addresses-with-php-preg-replace
        Pattern Explanations:

        IPv4:

            /         #Pattern delimiter
            \.        #Match dot literally
            \d*       #Match zero or more digits
            $         #Match the end of the string
            /         #Pattern delimiter
        IPv6

            /         #Pattern delimiter
            [\da-f]*  #Match zero or more digits or a b c d e f
            :         #Match colon
            [\da-f]*  #Match zero or more digits or a b c d e f
            $         #Match the end of the string
            /         #Pattern delimiter
    **/
    $ipv4 = '.' .(string) mt_rand(100,999);
    $ipv6 = (string) mt_rand(1000,9999) . ':' . (string) mt_rand(1000,9999);

    return preg_replace(
        array(
            '/\.\d*$/',
            '/[\da-f]*:[\da-f]*$/'
        ),
        array(
            $ipv4,
            $ipv6
        ),
        $ip
    );
}
add_filter( 'monsterinsights_mp_api_call_ip', 'monsterinsights_eu_compliance_mp_api_call_ip' );

// Remove userIDs from the MP calls
function monsterinsights_eu_compliance_mp_api_call_uid( $body ) {
    if ( monsterinsights_eu_compliance_require_optin() ) {
        return $body;
    }
    unset( $body['uid'] );
    return $body;
}
add_filter( 'monsterinsights_mp_api_call', 'monsterinsights_eu_compliance_mp_api_call_uid' );

function monsterinsights_eu_compliance_tracking_analytics_script_attributes( $attributes ) {
    if ( function_exists( 'cookiebot_active' ) && cookiebot_active() ) {
        $attributes['type'] = 'text/plain';
        $attributes['data-cookieconsent'] = 'statistics';
    }
    return $attributes;
}
add_filter( 'monsterinsights_tracking_analytics_script_attributes',  'monsterinsights_eu_compliance_tracking_analytics_script_attributes' );

function monsterinsights_eu_compliance_cookie_notice_integration() {
    if ( ! class_exists( 'Cookie_Notice' ) ) {
        return;
    }
    ob_start();
    ?>
    /* Compatibility with Cookie Notice */
    if ( document.cookie.indexOf( 'cookie_notice_accepted' ) === -1 ) {
        mi_track_user      = false;
        mi_no_track_reason = '<?php echo esc_js( __( "Note: You have not accepted the Cookie Notice.", "monsterinsights-eu-compliance" ) );?>';
    } else {
        var mi_cn_value = document.cookie;
        var mi_cn_name = 'cookie_notice_accepted';
        var mi_cn_starts_at = mi_cn_value.indexOf(" " + mi_cn_name + "=");
        if (mi_cn_starts_at == -1) {
            mi_cn_starts_at = mi_cn_value.indexOf(mi_cn_name + "=");
        }
        if (mi_cn_starts_at == -1) {
            mi_cn_value = null;
        } else {
            mi_cn_starts_at = mi_cn_value.indexOf("=", mi_cn_starts_at) + 1;
            var mi_cn_ends_at = mi_cn_value.indexOf(";", mi_cn_starts_at);
            if (mi_cn_ends_at == -1) {
                mi_cn_ends_at = mi_cn_value.length;
            }
            mi_cn_value = unescape(mi_cn_value.substring(mi_cn_starts_at,mi_cn_ends_at));
        }
        if ( mi_cn_value !== 'true' ) {
            mi_track_user      = false;
            mi_no_track_reason = '<?php echo esc_js( __( "Note: You declined cookies on the Cookie Notice consent bar.", "monsterinsights-eu-compliance" ) );?>';
        }
    }
    <?php
    $output = ob_get_contents();
    ob_end_clean();
    echo $output;
}
add_action( 'monsterinsights_tracking_analytics_frontend_output_after_mi_track_user', 'monsterinsights_eu_compliance_cookie_notice_integration' );