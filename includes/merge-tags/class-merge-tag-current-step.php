<?php
/**
 * Gravity Flow Current Step Merge Tag
 *
 * @package     GravityFlow
 * @copyright   Copyright (c) 2015-2018, Steven Henty S.L.
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 */

if ( ! class_exists( 'GFForms' ) ) {
	die();
}

/**
 * Class Gravity_Flow_Merge_Tag_Current_Step
 *
 * @since 2.2.3
 */
class Gravity_Flow_Merge_Tag_Current_Step extends Gravity_Flow_Merge_Tag {

	/**
	 * The name of the merge tag.
	 *
	 * @since 2.2.3-dev
	 *
	 * @var string
	 */
	public $name = 'current_step';

	/**
	 * The regular expression to use for the matching.
	 *
	 * @since 2.2.3-dev
	 *
	 * @var string
	 */
	protected $regex = '/{current_step(:(.*?))?}/';

	/**
	 * Replace the {current_step} merge tags.
	 *
	 * @since 2.2.3-dev
	 *
	 * @param string $text The text to be processed.
	 *
	 * @return string
	 */
	public function replace( $text ) {

		$matches = $this->get_matches( $text );

		if ( ! empty( $matches ) ) {

			if ( empty( $this->entry ) || empty( $this->step ) ) {
				foreach ( $matches as $match ) {
					$full_tag = $match[0];
					$text = str_replace( $full_tag, '', $text );
				}
				return $text;
			}

			$current_step = $this->step;

			foreach ( $matches as $match ) {
				$full_tag = $match[0];
				$property = isset( $match[2] ) ? $match[2] : 'name';

				switch ( $property ) :
					case 'duration':
						$duration = time() - $current_step->get_step_timestamp();
						$value = gravity_flow()->format_duration( $duration );
						break;

					case 'duration_minutes':
						$value = floor( ( time() - $current_step->get_step_timestamp() ) / 60 );
						break;

					case 'duration_seconds':
						$value = time() - $current_step->get_step_timestamp();
						break;

					case 'expiration':
						$expiration_date = $current_step->get_expiration_timestamp();
						if ( false !== $expiration_date ) {
							$expiration_date_str = date( 'Y-m-d H:i:s', $expiration_date );
							$value = get_date_from_gmt( $expiration_date_str );
						} else {
							$value = '';
						}
						break;

					case 'ID':
						$value = $current_step->get_id();
						break;

					case 'schedule':
						if ( $current_step->scheduled ) {
							$scheduled_timestamp = $current_step->get_schedule_timestamp();
							switch ( $current_step->schedule_type ) {
								case 'date':
									$value = $current_step->schedule_date;
									break;
								case 'date_field':
									$scheduled_date_str = date( 'Y-m-d H:i:s', $scheduled_timestamp );
									$value = get_date_from_gmt( $scheduled_date_str );
									break;
								case 'delay':
								default:
									$scheduled_date_str = date( 'Y-m-d H:i:s', $scheduled_timestamp );
									$value = get_date_from_gmt( $scheduled_date_str );
							}
						} else {
							$value = '';
						}
						break;

					case 'start':
						$step_date_str = date( 'Y-m-d H:i:s', $current_step->get_step_timestamp() );
						$value = get_date_from_gmt( $step_date_str );
						break;

					case 'type':
						$value = $current_step->get_type();
						break;

					default:
						$value = $current_step->get_name();

				endswitch;
				$text = str_replace( $full_tag, $this->format_value( $value ), $text );
			}
			return $text;
		}

		return $text;
	}
}

Gravity_Flow_Merge_Tags::register( new Gravity_Flow_Merge_Tag_Current_Step );
