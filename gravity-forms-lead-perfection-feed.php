<?php
/*
Plugin Name: Gravity Forms - Lead Perfection feed add-on
Plugin URI: http://www.ninthlink.com
Description: A Gravity Forms add-on to connect GForms submits to Lead Perfection CRM
Version: 1.0
Author: TimS @ Ninthlink
Author URI: http://www.ninthlink.com
Documentation: http://www.gravityhelp.com/documentation/page/GFAddOn

------------------------------------------------------------------------
Copyright 2014 Ninthlink, Inc.

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA 02111-1307 USA
*/

//exit if accessed directly
if(!defined('ABSPATH')) exit;

//------------------------------------------
if (class_exists("GFForms")) {
    GFForms::include_feed_addon_framework();

    class GFLeadPerfection extends GFFeedAddOn {

        protected $_version = "1.0";
        protected $_min_gravityforms_version = "1.7.9999";
        protected $_slug = "gravity-forms-lead-perfection-feed";
        protected $_path = "gravity-forms-lead-perfection-feed/gravity-forms-lead-perfection-feed.php";
        protected $_full_path = __FILE__;
        protected $_title = "Lead Perfection Plugin Settings";
        protected $_short_title = "Lead Perfection";

        // custom data vars for use outside class
        public $_feed_result = array();

        // constructor to assign plugin setting data to custom vars above
        public function __construct() {
            parent::__construct();
        }

        // Plugin Settings Page :: Forms -> Avala API Feed
        public function plugin_page() {
            echo '<p>This add-on allows forms data to be passed to the Lead Perfection system via HTTP.</p>';
            echo '<p>To use this add-on you must first add the HTTP POST URL on the <a href="' . $this->get_plugin_settings_url() . '">Lead Perfection Settings</a> page.</p>';
            echo '<p>To link your form(s) to a Lead Perfection feed:</p>';
            echo '<ol><li>Create your new form</li><li>Go to Form Settings >> Lead Perfection and create a new Lead Perfection feed</li><li>Name your feed and map the necessary Form Fields to the associated Lead Perfection field</li><li>Here you can also configure the field to trigger conditionally based on field values</li></ol>';
            echo '<p><strong>Iref Override:</strong> When creating a new form, in order to track PPC campaigns you must create a hidden field and dynamically populate that field based on the "src" query parameter. This field must then be mapped to the Iref Override in the feed settings.</p>';
        }

        /**
         *  Feed Settings Fields
         *
         *  Each form uses unique feed settings to connect with Avala. This allows extra refining for each circumstance
         *
         **/
        public function feed_settings_fields() {
            
            // array of settings fields
            $a = array(
                array(
                    "title"  => "Lead Perfection Form Field Settings",
                    "fields" => array(
                        array(
                            "label"   => "Feed Name",
                            "type"    => "text",
                            "name"    => "feedName",
                            "class"   => "small"
                        ),
                        array(
                            "name" => "lpMappedField",
                            "label" => "Fields",
                            "type" => "field_map",
                            "tooltip" => "Map each Lead Perfection Field to Gravity Form Field",
                            "field_map" => array(
                                array("name" => "Fname","label" => "First Name","required" => 0),
                                array("name" => "Lname","label" => "Last Name","required" => 0),
                                array("name" => "Address1","label" => "Address","required" => 0),
                                array("name" => "Address2","label" => "Address (line 2)","required" => 0),
                                array("name" => "City","label" => "City","required" => 0),
                                array("name" => "State","label" => "State","required" => 0),
                                array("name" => "Zipcode","label" => "Zip Code","required" => 0),
                                array("name" => "Phone","label" => "Phone","required" => 0),
                                array("name" => "Email","label" => "Email Address","required" => 0),
                                array("name" => "Ht_date","label" => "Ht date","required" => 0),
                            ),
                        ),
                        array(
                            "name" => "lpMappedFieldComments",
                            "label" => "Comments Meta",
                            "type" => "field_map",
                            "tooltip" => "Map each Lead Perfection Field to Gravity Form Field",
                            "field_map" => array(
                                array("name" => "MailingList","label" => "Mailing List","required" => 0),
                                array("name" => "SIDate","label" => "Site Inspection Date","required" => 0),
                                array("name" => "SITime","label" => "Site Inspection Time","required" => 0),
                            ),
                        ),
                        array(
                            "label" => "Iref Default",
                            "name" => "iref",
                            "type" => "select",
                            "required" => true,
                            "class" => "small",
                            "choices" => array(
                                array("label" => "Homepage", "value" => "IHOME"),
                                array("label" => "Design Your Own", "value" => "IDYO"),
                                array("label" => "Brochure / DVD", "value" => "iDVD"),
                                array("label" => "Sidebar", "value" => "iSide"),
                                array("label" => "Pricing", "value" => "iPrice"),
                                array("label" => "Site Inspection", "value" => "iSite"),
                            ),
                        ),
                        array(
                            "name" => "lpMappedFieldIref",
                            "label" => "Iref Override Field",
                            "type" => "field_map",
                            "tooltip" => "Map a field to override the default Iref setting",
                            "field_map" => array(
                                array("name" => "Iref", "label" => "Iref", "required" => 0),
                            ),
                        ),
                        array(
                            "name" => "feedCondition",
                            "label" => "Conditional",
                            "type" => "feed_condition",
                            "checkbox_label" => "Enable Condition",
                            "instructions" => "Process this feed if",
                        )
                    )
                )
            );

            return $a;
        }

        /**
         *  Field Map "Field" title updated
         *
         **/
        public function field_map_title() {
            return "Lead Perfection Field";
        }

        /**
         *  Columns displayed on Feed overview / list page
         *
         **/
        public function feed_list_columns() {
            return array(
                'feedName' => 'Name',
            );
        }

        /**
         *  Plugin Settings Fields
         *
         *  These setting apply to entire plugin, not just individual feeds
         *
         **/
        public function plugin_settings_fields() {
            return array(
                array(
                    "title"  => "Lead Perfection Feed Settings",
                    "fields" => array(
                        array(
                            "name"    => "feed_postURL",
                            "label"   => "Feed URL",
                            "type"    => "text",
                            "class"   => "medium"
                        ),
                    ),
                ),
            );
        }

        /**
         *  Feed Processor
         *
         *  This is the nuts and bolts: all actions to happen on form submit happen here
         *  Feed processing happens after submit, but before page redirect/thanks message
         *
         **/
        public function process_feed($feed, $entry, $form){

            // working vars
            $comments = array();
            $url = $this->get_plugin_setting('feed_postURL');
            //if ( ! $url ) return false;
            
            // current user info
            global $current_user;
            get_currentuserinfo();

            // The full array of data that will be translated into Avala API data
            $array = array(
                'Fname'      => is_user_logged_in() ? $current_user->user_firstname : '',
                'Lname'      => is_user_logged_in() ? $current_user->user_lastname : '',
                'Address1'   => '',
                'Address2'   => '',
                'City'       => '',
                'State'      => '',
                'Zipcode'    => '',
                'Email'      => is_user_logged_in() ? $current_user->user_email : '',
                'Phone'      => '',
                'Comments'   => '',
                'Ht_date'    => '',
                'Iref'       => $feed['meta']['iref'],
            );

            // iterate over meta data mapped fields (from feed fields) and apply to the array above
            foreach ($feed['meta'] as $k => $v) {
                $l = explode("_", $k);
                if ( isset( $l[0] ) && $l[0] == 'lpMappedFieldComments' && !empty( $v ) ) :
                    switch ( $l[1] ) {
                        case 'MailingList':
                            $comments[] = 'Join Mailing List: ' . $v;
                            break;
                        case 'SIDate':
                            $comments[] = 'Site Inspection Request on Date: ' . $v;
                            break;
                        case 'SITime':
                            $comments[] = 'Time: ' . $v;
                            break;
                        default:
                            # code...
                            break;
                    }
                elseif ( isset( $l[1] ) && array_key_exists( $l[1], $array ) && !empty( $v ) ) :
                    $array[ $l[1] ] = $entry[ $v ];
                endif;
            }
            $array['Comments'] = implode(', ', $comments);

            // Remove empty ARRAY fields so we do not submit blank data
            $array = array_filter( $array );

            $url .= '?' . http_build_query( $array );

            $ch = curl_init( $url );
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            $response = curl_exec($ch);

            curl_close($ch);

            if ( strpos( $response, '[OK]' ) !== false )
                gform_update_meta($entry['id'], 'lead_perfection_response', '[OK]');

            print('<pre>');
            print_r($feed);
            print_r($entry);
            print('</pre>');
        }
        
        /**
         *  END of API ADD-ON CLASS
         *
         **/
    }

    // Instantiate the class - this triggers everything, makes the magic happen
    $gfa = new GFLeadPerfection();

}