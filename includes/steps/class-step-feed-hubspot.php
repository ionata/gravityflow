<?php
/**
 * Gravity Flow Step Feed HubSpot
 *
 *
 * @package     GravityFlow
 * @subpackage  Classes/Gravity_Flow_Step_Feed_HubSpot
 * @copyright   Copyright (c) 2016, Steven Henty
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       1.3.3-dev
 */

if ( ! class_exists( 'GFForms' ) ) {
	die();
}

class Gravity_Flow_Step_Feed_HubSpot extends Gravity_Flow_Step_Feed_Add_On {
	public $_step_type = 'hubspot';

	protected $_class_name = 'GF_HubSpot';

	public function get_label() {
		return esc_html__( 'HubSpot', 'gravityflow' );
	}
	
}
Gravity_Flow_Steps::register( new Gravity_Flow_Step_Feed_HubSpot() );
