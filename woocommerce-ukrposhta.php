<?php
/**
 * Plugin Name: WooCommerce Ukrposhta shipping
 * Plugin URI: https://github.com/zhuiks/woocommerce-ukrposhta
 * Description: Ukrposhta shipping method for Woocommerce.
 * Version: 0.1
 * Author: zk
 * Author URI: https://github.com/zhuiks
 * Requires at least: 4.4
 */
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

function ukrposhta_init() {
    if ( ! class_exists( 'WC_Ukrposhta_Shipping' ) ) {
        class WC_Ukrposhta_Shipping extends WC_Shipping_Method {
            /**
             * Constructor for your shipping class
             *
             * @access public
             * @return void
             */
            public function __construct( $instance_id = 0 ) {
                $this->id                   = 'ukrposhta';
                $this->instance_id          = absint( $instance_id );
                $this->method_title         = __( 'Ukrposhta Shipping' );
                $this->method_description   = __( 'Shipping by Ukranian Post' ); //
                $this->supports             = array(
                    'shipping-zones',
                    'instance-settings',
                    'instance-settings-modal',
                );
                $this->init();

            }

            /**
             * Init your settings
             *
             * @access public
             * @return void
             */
            function init() {
                // Load the settings API
                $this->init_form_fields(); // This is part of the settings API. Override the method to add your own settings
                $this->title            = $this->get_option( 'title' );
                $this->usd_rate		    = $this->get_option( 'usd_rate' );

                // Save settings in admin if you have any defined
                add_action( 'woocommerce_update_options_shipping_' . $this->id, array( $this, 'process_admin_options' ) );
            }

            function init_form_fields()
            {
                //parent::init_form_fields();
                $this->instance_form_fields = array(
                    'title' => array(
                        'title' 		=> __( 'Method Title' ),
                        'type' 			=> 'text',
                        'description' 	=> __( 'This controls the title which the user sees during checkout.' ),
                        'default'		=> __( 'Укрпошта' ),
                        'desc_tip'		=> true
                    ),
                    'usd_rate' => array(
                        'title' 		=> __( 'Курс долара' ),
                        'type' 			=> 'text',
                        'description' 		=> __( 'Для обрахунку вартості пересилки за кордон' ),
                        'default' 		=> '25',
                        'desc_tip'		=> true
                    ),
                );

            }

            /**
             * calculate_shipping function.
             *
             * @access public
             * @param mixed $package
             * @return void
             */
            public function calculate_shipping( $package = array() ) {
                // http://ukrposhta.ua/dovidka/tarifi/universalni-poslugi
                $ukrposhta_rates = array(
                    'UA' => array(5.60, 4.00),
                    'AZ' => array(12.00, 2.80, 2.60),
                    'BY' => array(9.20, 2.50, 2.00),
                    'AM' => array(13.70, 2.45, 2.05),
                    'GE' => array(11.20, 3.00, 2.80),
                    'KZ' => array(9.90, 4.45, 2.50),
                    'KG' => array(9.90, 3.30, 2.30),
                    'MD' => array(13.00, 2.80, 2.50),
                    'TJ' => array(8.70, 3.25, 2.20),
                    'TM' => array(7.75, 2.85, 1.95),
                    'UZ' => array(14.00, 3.60, 2.65),
                    'RU' => array(15.70, 3.50, 2.15),
                    'regions' => array(
                        'east_europe'       => array(13.70, 2.50, 2.10, 'countries' => array('AL','BA','BG','EE','LT','LV','MK','PL','RO','RS','SK','SI','HU','HR','CZ','ME')), // в тарифному документі укрпошти є ще Югославія. Але в 2015р. такої держави вже не існувало...
                        'central_europe'    => array(16.50, 2.80, 2.20, 'countries' => array('AT','GR','DK','LI','DE','NO','FI','SE','CH')),
                        'west_europe'       => array(16.40, 3.30, 2.10, 'countries' => array('AD','BE','VA','GB','GI','IT','ES','IE','IS','LU','MT','MC','NL','PT','SM','FR','FO')), //не знайшов коду Канарських островів
                        'asia'              => array(14.00, 2.60, 2.00, 'countries' => array('AF','BH','IL','IN','IQ','IR','JO','YE','QA','CY','KW','LB','MV','AE','OM','PK','PS','SA','SY','TR','LK')), //не знайшов коду Дієго Гарсія
                        'north_america'     => array(10.80, 5.80, 3.30, 'countries' => array('CA','US','GL')),
                        'east_asia'         => array(12.00, 7.10, 3.60, 'countries' => array('BT','BN','BD','VN','HK','ID','KP','KR','KH','CN','LA','MM','MY','MN','MO','NP','PG','SG','TH','TW','PH','JP')),
                        'africa_south_america' => array(14.50, 7.00, 5.50, 'countries' => array('DZ','AO','AI','AG','BQ','AR','AW','BS','BB','BZ','BJ','BM','BO','BW','BR','VG','BF','BI','VI','VE','GA','HT','GY','GM','GH','GP','GT','GN','GW','HN','GD','DM','DO','DJ','EC','GQ','ER','ET','EG',
'EH','ZM','ZW','CM','CV','KE','CO','KM','CG','CD','CR','CI','CU','CW','LS','LR','LY','MR','MU','MG','YT','MW','ML','MP','MA','MQ','MX','MZ','MS','NA','NI','NE','NG','KY','SH','PA','PY','PE','ZA','PR','RE','RW',
'SV','ST','SZ','SC','SN','VC','KN','LC','PM','MF','SX','SO','SL','SS','SD','SR','TZ','TC','TG','TT','TN','UG','UY','FK','GF','TF','CF','TD','CL','JM')),
                        'australia'         => array(10.40, 11.50, 6.00, 'countries' => array('AU','AS','AQ','VU','GU','WS','KI','CC','MH','FM','NR','NU','NZ','NC','NF','CX','PW','CK','PN','SB','TK','TO','TV','WF','FJ','PF')),//не знайшов коду Мідуей, острова Пасхи
                    )
                );
                $rate = array(
                    'id' => $this->get_rate_id(),
                    'label' => $this->title,
                    'cost'    => 0,
                    'package' => $package,
                );
                $package_weight = 0;
                foreach ( $package['contents'] as $item_id => $values ) {
                    // skip products that dont need shipping
                    if ( $values['data']->needs_shipping() ) {
                        // make sure a weight is set
                        if ( $values['data']->get_weight() ) {
                            $package_weight += $values['data']->get_weight();
                        }
                    }
                }
                $package_weight = ceil(10*wc_get_weight($package_weight, 'kg'))/10;

                $destination = isset($package['destination']['country']) ? $package['destination']['country'] : false;
                if($destination && $package_weight) {
                    if(isset($ukrposhta_rates[$destination])) {
                        $rate['cost'] = $ukrposhta_rates[$destination][0] + $ukrposhta_rates[$destination][1] * $package_weight;
                    } else {
                        foreach($ukrposhta_rates['regions'] as $ukrposhta_region) {
                            if(in_array($destination, $ukrposhta_region['countries'])) {
                                $rate['cost'] = $ukrposhta_region[0] + $ukrposhta_region[1] * $package_weight;
                                break;
                            }
                        }
                    }
                    if($destination!='UA') {
                        $rate['cost'] = $rate['cost'] * $this->usd_rate;
                    }
                    $rate['cost'] *= 1.2; //20% VAT
                    //Check calculations here: http://ukrposhta.ua/ua/kalkulyator-forma-rozraxunku
                    // Register the rate
                    $this->add_rate($rate);
                }
            }
        }
    }
}

add_action( 'woocommerce_shipping_init', 'ukrposhta_init' );

function add_ukrposhta_shipping( $methods ) {
    $methods['ukrposhta'] = 'WC_Ukrposhta_Shipping';
    return $methods;
}

add_filter( 'woocommerce_shipping_methods', 'add_ukrposhta_shipping' );

//add UA states
add_filter( 'woocommerce_states', 'ua_woocommerce_states' );

function ua_woocommerce_states( $states ) {

    $states['UA'] = array(
        'Vinnytsa'  => 'Вінницька',
        'Volyn'     => 'Волинська',
        'Dnipro'    => 'Дніпропетровська',
        'Donetsk'   => 'Донецька',
        'Zhytomyr'  => 'Житомирська',
        'Karpatska' => 'Закарпатська',
        'Zaporizhzhya'  => 'Запорізька',
        'Ivano-Frankivsk' => 'Івано-Франківська',
        'Kyivskoi'  => 'Київська',
        'Kirovohrad'    => 'Кіровоградська',
        'Luhansk'   => 'Луганська',
        'Lviv'      => 'Львівська',
        'Mykolaiv'  => 'Миколаївська',
        'Odesa'     => 'Одеська',
        'Poltava'   => 'Полтавська',
        'Rivne'     => 'Рівненська',
        'Sumy'      => 'Сумська',
        'Ternopil'  => 'Тернопільська',
        'Kharkiv'   => 'Харківська',
        'Kherson'   => 'Херсонська',
        'Khmelnytskyi'  => 'Хмельницька',
        'Cherkasy'  => 'Черкаська',
        'Chernivtsi'    => 'Чернівецька',
        'Chernihiv' => 'Чернігівська',
    );

    return $states;
}
