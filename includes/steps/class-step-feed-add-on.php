<?php
/**
 * Gravity_Flow_Step_Feed_Add_On
 *
 *
 * @package     GravityFlow
 * @subpackage  Classes/Step_Feed_Add_On
 * @copyright   Copyright (c) 2015, Steven Henty
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       1.0
 */


if ( ! class_exists( 'GFForms' ) ) {
	die();
}
/**
 * Abstract class to be used for integration with Gravity Forms Feed Add-Ons.
 * Extend this class to integrate any Gravity Forms Add-On that is built using the Feed-Add-On Framework.
 *
 * Register your extending class using Gravity_Flow_Steps::register().
 * example:
 * Gravity_Flow_Steps::register( new Gravity_Flow_Step_My_Feed_Add_On_Step() )
 *
 * Class Gravity_Flow_Step_Feed_Add_On
 *
 * @since		1.0
 */
abstract class Gravity_Flow_Step_Feed_Add_On extends Gravity_Flow_Step {

	/**
	 * The name of the class used by the add-on. Example: GFMailChimp.
	 *
	 * @var string
	 */
	protected $_class_name = '';

	/**
	 * The add-on slug. Example: gravityformsmailchimp.
	 *
	 * @var string
	 */
	protected $_slug = '';

	/**
	 * Returns the class name for the add-on.
	 *
	 * @return string
	 */
	public function get_feed_add_on_class_name() {
		return $this->_class_name;
	}

	/**
	 * Is this feed step supported on this server? Override to hide this step in the list of step types if the requirements are not met.
	 *
	 * @return bool
	 */
	public function is_supported() {

		$is_supported      = true;
		$feed_add_on_class = $this->get_feed_add_on_class_name();
		if ( ! class_exists( $feed_add_on_class ) ) {
			$is_supported = false;
		}

		return $is_supported;
	}

	/**
	 * Returns the settings for this step.
	 *
	 * @return array
	 */
	public function get_settings() {

		$fields = array();

		if ( ! $this->is_supported() ) {
			return $fields;
		}

		$feeds = $this->get_feeds();

		$feed_choices = array();
		foreach ( $feeds as $feed ) {
			if ( $feed['is_active'] ) {
				$label = $this->get_feed_label( $feed );

				$feed_choices[] = array(
					'label' => $label,
					'name'  => 'feed_' . $feed['id'],
				);
			}
		}

		if ( ! empty( $feed_choices ) ) {
			$fields[] = array(
				'name'     => 'feeds',
				'required' => true,
				'label'    => esc_html__( 'Feeds', 'gravityflow' ),
				'type'     => 'checkbox',
				'choices'  => $feed_choices,
			);
		}

		if ( empty( $fields ) ) {
			$html = esc_html__( "You don't have any feeds set up.", 'gravityflow' );
			$fields[] = array(
				'name'  => 'no_feeds',
				'label' => esc_html__( 'Feeds', 'gravityflow' ),
				'type'  => 'html',
				'html'  => $html,
			);
		}

		return array(
			'title'  => $this->get_label(),
			'fields' => $fields,
		);
	}


	/**
	 * Processes this step.
	 *
	 * @return bool Is the step complete?
	 */
	public function process() {

		$form  = $this->get_form();
		$entry = $this->get_entry();

		$feeds = $this->get_feeds();
		foreach ( $feeds as $feed ) {
			$setting_key = 'feed_' . $feed['id'];
			if ( $this->{$setting_key} ) {
				if ( $this->is_feed_condition_met( $feed, $form, $entry ) ) {

					$this->process_feed( $feed );
					$label = $this->get_feed_label( $feed );
					$note  = sprintf( esc_html__( 'Feed processed: %s', 'gravityflow' ), $label );
					$this->add_note( $note, 0, $this->get_type() );
					$this->log_debug( __METHOD__ . '() - Feed processed' );
				} else {
					$this->log_debug( __METHOD__ . '() - Feed condition not met' );
				}
			}
		}

		return true;
	}

	/**
	 * Returns the feeds for the add-on.
	 *
	 * @return array|mixed
	 */
	public function get_feeds() {
		$form_id = $this->get_form_id();

		if ( $this->is_supported() ) {
			/* @var GFFeedAddOn $add_on */
			$add_on = $this->get_add_on_instance();
			$feeds  = $add_on->get_feeds( $form_id );
		} else {
			$feeds = array();
		}

		return $feeds;
	}

	/**
	 * Processes the given feed for the add-on.
	 *
	 * @param $feed
	 */
	public function process_feed( $feed ) {
		$form   = $this->get_form();
		$entry  = $this->get_entry();
		$add_on = $this->get_add_on_instance();

		$add_on->process_feed( $feed, $entry, $form );
	}

	/**
	 * Prevent the feeds assigned to the current step from being processed by the associated add-on.
	 */
	function intercept_submission() {
		$form_id = $this->get_form_id();
		if ( gravity_flow()->is_gravityforms_supported( '2.0-beta-2' ) ) {
			$slug = $this->get_slug();
			add_filter( "gform_{$slug}_pre_process_feeds_{$form_id}", array( $this, 'pre_process_feeds' ), 10, 2 );
		} else {
			add_filter( "gform_is_delayed_pre_process_feed_{$form_id}", array( $this, 'is_delayed_pre_process_feed' ), 10, 4 );
		}
	}

	/**
	 * Returns the label of the given feed.
	 *
	 * @param $feed
	 *
	 * @return mixed
	 */
	function get_feed_label( $feed ) {
		$label = $feed['meta']['feedName'];

		return $label;
	}

	/**
	 * Determines if the supplied feed should be processed. 
	 * 
	 * @param array $feed The current feed.
	 * @param array $form The current form.
	 * @param array $entry The current entry.
	 *
	 * @return bool
	 */
	public function is_feed_condition_met( $feed, $form, $entry ) {

		return gravity_flow()->is_feed_condition_met( $feed, $form, $entry );
	}

	/**
	 * Retrieve an instance of the add-on associated with this step.
	 *
	 * @return GFFeedAddOn
	 */
	public function get_add_on_instance() {
		$add_on = call_user_func( array( $this->get_feed_add_on_class_name(), 'get_instance' ) );

		return $add_on;
	}

	/**
	 * Remove the feeds assigned to the current step from the array to be processed by the associated add-on.
	 *
	 * @param array $feeds An array of $feed objects for the add-on currently being processed.
	 * @param array $entry The entry object currently being processed.
	 *
	 * @return array
	 */
	public function pre_process_feeds( $feeds, $entry ) {
		if ( is_array( $feeds ) ) {
			foreach ( $feeds as $key => $feed ) {
				$setting_key = 'feed_' . $feed['id'];
				if ( $this->{$setting_key} ) {
					$this->get_add_on_instance()->log_debug( __METHOD__ . "(): Delaying feed (#{$feed['id']} - {$this->get_feed_label( $feed )}) for entry #{$entry['id']}." );
					unset( $feeds[ $key ] );
				}
			}
		}

		return $feeds;
	}

	/**
	 * Prevent the feeds assigned to the current step from being processed by the associated add-on.
	 *
	 * @param bool $is_delayed Is feed processing delayed?
	 * @param array $form The form object currently being processed.
	 * @param array $entry The entry object currently being processed.
	 * @param string $slug The Add-On slug e.g. gravityformsmailchimp
	 * 
	 * @todo Remove once min GF version reaches 2.0.
	 *
	 * @return bool
	 */
	public function is_delayed_pre_process_feed( $is_delayed, $form, $entry, $slug ) {
		if ( $slug == $this->get_slug() ) {
			$feeds = $this->get_feeds();
			if ( is_array( $feeds ) ) {
				foreach ( $feeds as $feed ) {
					$setting_key = 'feed_' . $feed['id'];
					if ( $this->{$setting_key} ) {
						$this->get_add_on_instance()->log_debug( __METHOD__ . "(): Delaying feed (#{$feed['id']} - {$this->get_feed_label( $feed )}) for entry #{$entry['id']}." );

						return true;
					}
				}
			}
		}

		return $is_delayed;
	}

	/**
	 * Ensure active steps are not processed if the associated add-on is not available.
	 *
	 * @return bool
	 */
	public function is_active() {
		$is_active = parent::is_active();

		if ( $is_active && ! $this->is_supported() ) {
			$is_active = false;
		}

		return $is_active;
	}

	/**
	 * Get the slug for the add-on associated with this step.
	 * 
	 * @return string
	 */
	function get_slug() {
		$slug = $this->_slug;
		if ( ! $slug ) {
			if ( gravity_flow()->is_gravityforms_supported( '2.0-beta-3' ) ) {
				$slug = $this->get_add_on_instance()->get_slug();
			} else {
				$slug = 'gravityforms' . str_replace( '_', '', $this->get_type() );
			}

			$this->_slug = $slug;
		}

		return $slug;
	}

}
