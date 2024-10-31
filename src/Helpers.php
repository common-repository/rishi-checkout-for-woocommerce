<?php

namespace WC_Checkout_PRO;

if ( ! defined( 'ABSPATH' ) ) {
  exit; // Exit if accessed directly.
}

Class Helpers {
  public static function sanitize_postcode( $postcode ) {
    return preg_replace( '([^0-9])', '', sanitize_text_field( $postcode ) );
  }

  public static function get_state( $postcode ) {
    $postcode = self::sanitize_postcode( $postcode );
    $ranges   = array(
      array( '69900-000', '69999-999', 'AC' ),
      array( '57000-000', '57999-999', 'AL' ),
      array( '69000-000', '69299-999', 'AM' ),
      array( '69400-000', '69899-999', 'AM' ),
      array( '68900-000', '68999-999', 'AP' ),
      array( '40000-000', '48999-999', 'BA' ),
      array( '60000-000', '63999-999', 'CE' ),
      array( '70000-000', '72799-999', 'DF' ),
      array( '73000-000', '73699-999', 'DF' ),
      array( '29000-000', '29999-999', 'ES' ),
      array( '72800-000', '72999-999', 'GO' ),
      array( '73700-000', '76799-999', 'GO' ),
      array( '65000-000', '65999-999', 'MA' ),
      array( '30000-000', '39999-999', 'MG' ),
      array( '79000-000', '79999-999', 'MS' ),
      array( '78000-000', '78899-999', 'MT' ),
      array( '66000-000', '68899-999', 'PA' ),
      array( '58000-000', '58999-999', 'PB' ),
      array( '50000-000', '56999-999', 'PE' ),
      array( '64000-000', '64999-999', 'PI' ),
      array( '80000-000', '87999-999', 'PR' ),
      array( '20000-000', '28999-999', 'RJ' ),
      array( '59000-000', '59999-999', 'RN' ),
      array( '76800-000', '76999-999', 'RO' ),
      array( '69300-000', '69399-999', 'RR' ),
      array( '90000-000', '99999-999', 'RS' ),
      array( '88000-000', '89999-999', 'SC' ),
      array( '49000-000', '49999-999', 'SE' ),
      array( '01000-000', '19999-999', 'SP' ),
      array( '77000-000', '77999-999', 'TO' ),
    );

    foreach ( $ranges as $range ) {
      if ( $postcode >= $range[0] && $postcode <= $range[1] ) {
        return $range[2];
      }
    }

     return '';
  }
}
