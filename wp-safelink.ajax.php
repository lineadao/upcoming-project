<?php
/**
 * WP Safelink Ajax
 *
 * @author ThemesOn
 * @package WP Safelink (Server Version)
 * @since 1.0.0
 */
if (!defined('ABSPATH')) {
    exit;
} // Exit if accessed directly

if (!class_exists('WPSafelink_Ajax')):

    class WPSafelink_Ajax
    {

        /* @var string */
        private static $nonce_admin = 'wpsafelink_nonce';

        /**
         * Constructor
         *
         * @return void
         * @since 1.0.0
         */
        public function __construct()
        {
            // ajax_event => nopriv
            $ajax_event = array(
                'generate_link' => false
            );
            foreach ($ajax_event as $ajax_event => $nopriv) {
                add_action('wp_ajax_wpsafelink_' . $ajax_event, array(__CLASS__, $ajax_event));
                if ($nopriv) {
                    add_action('wp_ajax_nopriv_wpsafelink_' . $ajax_event, array(__CLASS__, $ajax_event));
                }
            }
        }

        /**
         * Generate link
         *
         * @access public
         * @return json
         * @since 1.0.0
         **/
        public static function generate_link()
        {
            global $wpdb, $WPSAF;
            $wpsaf = json_decode(get_option('wpsaf_options'));

            $output = [];
            $output['data'] = [];

            /*
             * Paging
             */
            $sLimit = "";
            if (isset($_GET['start']) && $_GET['length'] != '-1') {
                $sLimit = "LIMIT " . $_GET['start'] . ", " .
                    $_GET['length'];
            }

            /*
             * Ordering
             */
            if (isset($_GET['order'])) {
                $aColumns = ["date", "", "safe_id", "link", "view", "click"];
                $sOrder = "ORDER BY  ";
                for ($i = 0; $i < intval($_GET['order']); $i++) {

                    $sOrder .= $aColumns[$_GET['order'][$i]['column']] . "
				 	" . $_GET['order'][$i]['dir'] . ", ";

                }

                $sOrder = substr_replace($sOrder, "", -2);
                if ($sOrder == "ORDER BY  ") {
                    $sOrder = "ORDER BY date DESC";
                }
            }
            /*
             * Search
             */
            if (isset($_GET['search'])) {
                $qSearch = "WHERE link LIKE '%{$_GET['search']['value']}%' ";
            }

            $sql = "SELECT * FROM {$wpdb->prefix}wpsafelink $qSearch $sOrder $sLimit";
            $safe_lists = $wpdb->get_results($sql, 'ARRAY_A');
            foreach ($safe_lists as $d) {
                $encrypted = $WPSAF->encrypt_link($d['link'], $d['safe_id']);
                if ($wpsaf->permalink == 1) {
                    $safelink_link = home_url() . '/' . $wpsaf->permalink1 . '/' . $d['safe_id'];
                    $safelink_links = home_url() . '/' . $wpsaf->permalink1 . '/' . base64_encode($d['link']);
                    $encrypt_link = home_url() . '/' . $wpsaf->permalink1 . '/' . $encrypted;
                } else if ($wpsaf->permalink == 2) {
                    $safelink_link = home_url() . '/?' . $wpsaf->permalink2 . '=' . $d['safe_id'];
                    $safelink_links = home_url() . '/?' . $wpsaf->permalink2 . '=' . base64_encode($d['link']);
                    $safelink_links = home_url() . '/?' . $wpsaf->permalink2 . '=' . $encrypted;
                    $encrypt_link = home_url() . '/?' . $wpsaf->permalink2 . '=' . $encrypted;
                } else {
                    $safelink_link = home_url() . '/?' . $d['safe_id'];
                    $safelink_links = home_url() . '/?' . base64_encode($d['link']);
                    $safelink_links = home_url() . '/?' . $encrypted;
                    $encrypt_link = home_url() . '/?' . $encrypted;
                }

                $temp = [];
                $temp[] = date('Y-m-d H:i', strtotime($d['date']));
                $temp[] = ($d['safe_id'] != "" ? "<a class='elips' href='" . $encrypt_link . "' target='_blank'>" . $encrypt_link . "</a>" : "");
                $temp[] = ($d['safe_id'] != "" ? "<a class='elips' href='" . $safelink_link . "' target='_blank'>" . $safelink_link . "</a>" : "");
                $temp[] = ($d['link'] != "" ? "<a class='elips' href='" . $d['link'] . "' target='_blank'>" . $d['link'] . "</a>" : "");
                $temp[] = $d['view'];
                $temp[] = $d['click'];
                $temp[] = '<a href="?page=wp-safelink&delete=' . $d['ID'] . '">delete</a>';

                $output['data'][] = $temp;
            }

            $count_query = "select count(*) from {$wpdb->prefix}wpsafelink";
            $num = $wpdb->get_var($count_query);

            $output['recordsTotal'] = $num;
            $output['recordsFiltered'] = $num;
            wp_send_json($output);
        }

    }

    new WPSafelink_Ajax();
endif;
