<?php
/**
 * Sensei Course Participants Widget
 *
 * @author   WooThemes
 * @category Widgets
 * @package  Sensei/Widgets
 * @version  1.0.0
 * @extends  WC_Widget
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Sensei_Course_Participants_Widget extends WP_Widget {
	/**
	 * The widget CSS class
	 *
	 * @var    string
	 * @access protected
	 */
	protected $woo_widget_cssclass;

	/**
	 * The widget description
	 *
	 * @var    string
	 * @access protected
	 */
	protected $woo_widget_description;

	/**
	 * The widget ID
	 *
	 * @var    string
	 * @access protected
	 */
	protected $woo_widget_idbase;

	/**
	 * The widget title
	 *
	 * @var    string
	 * @access protected
	 */
	protected $woo_widget_title;

	/**
	 * Set the widget data and invokes the parent contructor.
	 *
	 * @since  1.1.0
	 * @return void
	 */
	public function __construct() {
		// Widget variable settings
		$this->woo_widget_cssclass    = 'widget_sensei_course_participants';
		$this->woo_widget_description = esc_html__( 'Displays a list of learners taking the current course, with links to their profiles (if public).', 'sensei-course-participants' );
		$this->woo_widget_idbase      = 'sensei_course_participants';
		$this->woo_widget_title       = esc_html__( 'Sensei - Course Participants', 'sensei-course-participants' );

		// Widget settings
		$widget_ops = array(
			'classname'   => $this->woo_widget_cssclass,
			'description' => $this->woo_widget_description,
		);

		// Widget control settings
		$control_ops = array(
			'width'   => 250,
			'height'  => 350,
			'id_base' => $this->woo_widget_idbase,
		);

		// Create the widget
		parent::__construct( $this->woo_widget_idbase, $this->woo_widget_title, $widget_ops, $control_ops );
	}

	/**
	 * Display the widget on the frontend.
	 *
	 * @since  1.0.0
	 * @param  array $args     Widget arguments.
	 * @param  array $instance Widget settings for this instance.
	 * @return void
	 */
	public function widget( $args, $instance ) {
		$before_widget = isset( $args['before_widget'] ) ? $args['before_widget'] : '';
		$after_widget  = isset( $args['after_widget'] ) ? $args['after_widget'] : '';
		$before_title  = isset( $args['before_title'] ) ? $args['before_title'] : '';
		$after_title   = isset( $args['after_title'] ) ? $args['after_title'] : '';
		$title         = isset( $instance['title'] ) ? $instance['title'] : '';
		$display       = 'list';
		$limit         = 5;
		$size          = 50;
		$order         = 'ASC';
		$orderby       = 'name';

		if ( ! ( is_singular( 'course' ) || is_singular( 'lesson' ) ||
			 is_singular( 'quiz' ) || is_tax( 'module' ) ) ) {
			return;
		}

		if ( isset( $instance['title'] ) ) {
			$title = apply_filters( 'widget_title', $instance['title'], $instance, $this->id_base );
		}

		if ( isset( $instance['limit'] ) && ( 0 < intval( $instance['limit'] ) ) ) {
			$limit = intval( $instance['limit'] );
		}

		if ( isset( $instance['size'] ) && ( 0 < intval( $instance['size'] ) ) ) {
			$size = intval( $instance['size'] );
		}

		// Select boxes
		if ( isset( $instance['display'] ) && in_array( $instance['display'], array_keys( $this->get_display_options() ) ) ) {
			$display = $instance['display'];
		}

		if ( isset( $instance['orderby'] ) && in_array( $instance['orderby'], array_keys( $this->get_orderby_options() ) ) ) {
			$orderby = $instance['orderby'];
		}

		if ( isset( $instance['order'] ) && in_array( $instance['order'], array_keys( $this->get_order_options() ) ) ) {
			$order = $instance['order'];
		}

		/**
		 * @var WP_User[] $learners
		 */
		$learners        = Sensei_Course_Participants()->get_course_learners( $order, $orderby );
		$public_profiles = false;

		if ( isset( Sensei()->settings->settings[ 'learner_profile_enable' ] ) && Sensei()->settings->settings[ 'learner_profile_enable' ] ) {
			$public_profiles = true;
		}

		// Frontend Output
		echo $before_widget;

		// Display the widget title if one was input
		if ( $title ) {
			echo $before_title . esc_html( $title ) . $after_title;
		}

		// Add actions for plugins/themes to hook onto
		do_action( $this->woo_widget_cssclass . '_top' );

		$html = '';

		if ( false === $learners ) {
			$html .= '<p>' . esc_html__( 'There are no other learners currently taking this course. Be the first!', 'sensei-course-participants' ) . '</p>';
		} else {
			$list_class = 'grid' === $display ? 'grid' : 'list';

			$html .= '<ul class="sensei-course-participants-list' . ' ' . esc_attr( $list_class ) . '">';

			// Begin templating logic
			$tpl = '<li class="sensei-course-participant fix %%CLASS%%">%%IMAGE%%%%TITLE%%</li>';
			$tpl = wp_kses_post( apply_filters( 'sensei_course_participants_template', $tpl ) );

			$i = 0;
			foreach ( $learners as $learner ) {
				$template = $tpl;

				$i++;

				$class          = $i <= $limit ? 'show' : 'hide';
				$gravatar_email = sanitize_email( $learner->user_email );
				$image          = '<figure itemprop ="image">' . get_avatar( $gravatar_email, $size ) . '</figure>' . "\n";
				$learner_name   = '';
				$display_name   = $learner->display_name;

				if ( get_current_user_id() === $learner->ID ) {
					$display_name = esc_html__( 'You', 'sensei-course-participants' );
				}

				if ( 'list' === $display ) {
					$learner_name = '<span itemprop="name" class="learner-name">' . esc_html( $display_name ) . "</span>\n";
				}

				$profile_url = false;
				if ( true === $public_profiles ) {
					$profile_url  = Sensei()->learner_profiles->get_permalink( $learner->ID );
				}

				/**
				 * Filter the learner profile URL for course participants.
				 *
				 * @since 2.0.0
				 *
				 * @param string|bool $profile_url The learner's public profile URL if public profiles are enabled. Otherwise, this will be false.
				 * @param WP_User     $learner     The learner being displayed.
				 *
				 * @return string|bool The profile URL to be used, or false.
				 */
				$profile_url = apply_filters( 'sensei_course_participants_profile_url', $profile_url, $learner );

				if ( $profile_url ) {
					$link         = '<a href="' . esc_url( $profile_url ) . '" title="' . esc_attr__( 'View public learner profile', 'sensei-course-participants' ) . '">';
					$image        = $link . $image . '</a>';
					$learner_name = $link . $learner_name . '</a>';
				}

				$template = str_replace( '%%CLASS%%', esc_attr( $class ), $template );
				$template = str_replace( '%%IMAGE%%', $image, $template );
				$template = str_replace( '%%TITLE%%', $learner_name, $template );

				$html .= $template;

			}
			$html .= '</ul>';


			// Display a view all link if not all learners are displayed
			if ( $limit < count( $learners ) ) {
				$html .= '<div class="sensei-view-all-participants"><a href="#">' . esc_html__( 'View all', 'sensei-course-participants' ) . '</a></div>';

			}

		}

		echo $html;

		// Add actions for plugins/themes to hook onto
		do_action( $this->woo_widget_cssclass . '_bottom' );

		echo $after_widget;
	}

	/**
	 * Method to update the settings from the form() method.
	 *
	 * @since  1.0.0
	 * @param  array $new_instance New settings.
	 * @param  array $old_instance Previous settings.
	 * @return array               Updated settings.
	 */
	public function update ( $new_instance, $old_instance ) {
		$instance = $old_instance;

		// Strip tags for title and limit to remove HTML (important for text inputs)
		$instance['title'] = strip_tags( $new_instance['title'] );
		$instance['limit'] = intval( $new_instance['limit'] );
		$instance['size']  = intval( $new_instance['size'] );

		// The select box is returning a text value, so we escape it
		$instance['orderby'] = esc_attr( $new_instance['orderby'] );
		$instance['order']   = esc_attr( $new_instance['order'] );
		$instance['display'] = esc_attr( $new_instance['display'] );

		return $instance;
	}

	/**
	 * The form on the widget control in the widget administration area.
	 * Make use of the get_field_id() and get_field_name() function when creating your form elements. This handles the confusing stuff.
	 *
	 * @since  1.0.0
	 * @param  array $instance The settings for this instance.
	 * @return void
	 */
	public function form( $instance ) {
		// Set up some default widget settings.
		// Make sure all keys are added here, even with empty string values.
		$defaults = array(
			'title'   => '',
			'limit'   => 5,
			'size'    => 50,
			'orderby' => 'user_registered',
			'order'   => 'ASC',
			'display' => 'list',
		);

		$instance = wp_parse_args( (array) $instance, $defaults );
		?>
		<!-- Widget Title: Text Input -->
		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>"><?php esc_html_e( 'Title (optional):', 'sensei-course-participants' ); ?></label>
			<input type="text" name="<?php echo esc_attr( $this->get_field_name( 'title' ) ); ?>"  value="<?php echo esc_attr( $instance['title'] ); ?>" class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>" />
		</p>
		<!-- Widget Limit: Text Input -->
		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'limit' ) ); ?>"><?php esc_html_e( 'Number of Learners (optional):', 'sensei-course-participants' ); ?></label>
			<input type="text" name="<?php echo esc_attr( $this->get_field_name( 'limit' ) ); ?>"  value="<?php echo esc_attr( $instance['limit'] ); ?>" class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'limit' ) ); ?>" />
		</p>
		<!-- Image Size: Text Input -->
		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'size' ) ); ?>"><?php esc_html_e( 'Image Size (in pixels):', 'sensei-course-participants' ); ?></label>
			<input type="text" name="<?php echo esc_attr( $this->get_field_name( 'size' ) ); ?>"  value="<?php echo esc_attr( $instance['size'] ); ?>" class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'size' ) ); ?>" />
		</p>
		<!-- Widget Order By: Select Input -->
		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'orderby' ) ); ?>"><?php esc_html_e( 'Order By:', 'sensei-course-participants' ); ?></label>
			<select name="<?php echo esc_attr( $this->get_field_name( 'orderby' ) ); ?>" class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'orderby' ) ); ?>">
			<?php foreach ( $this->get_orderby_options() as $k => $v ) { ?>
				<option value="<?php echo esc_attr( $k ); ?>"<?php selected( $instance['orderby'], $k ); ?>><?php echo esc_html( $v ); ?></option>
			<?php } ?>
			</select>
		</p>
		<!-- Widget Order: Select Input -->
		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'order' ) ); ?>"><?php esc_html_e( 'Order Direction:', 'sensei-course-participants' ); ?></label>
			<select name="<?php echo esc_attr( $this->get_field_name( 'order' ) ); ?>" class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'order' ) ); ?>">
			<?php foreach ( $this->get_order_options() as $k => $v ) { ?>
				<option value="<?php echo esc_attr( $k ); ?>"<?php selected( $instance['order'], $k ); ?>><?php echo esc_html( $v ); ?></option>
			<?php } ?>
			</select>
		</p>
		<!-- Widget Display: Select Input -->
		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'display' ) ); ?>"><?php esc_html_e( 'Display:', 'sensei-course-participants' ); ?></label>
			<select name="<?php echo esc_attr( $this->get_field_name( 'display' ) ); ?>" class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'display' ) ); ?>">
			<?php foreach ( $this->get_display_options() as $k => $v ) { ?>
				<option value="<?php echo esc_attr( $k ); ?>"<?php selected( $instance['display'], $k ); ?>><?php echo esc_html( $v ); ?></option>
			<?php } ?>
			</select>
		</p>

		<?php
	}

	/**
	 * Get an array of the available orderby options.
	 *
	 * @since  1.0.0
	 * @return array
	 */
	protected function get_orderby_options () {
		return array(
			'user_registered' => esc_html__( 'Date Registered', 'sensei-course-participants' ),
			'display_name'    => esc_html__( 'Name', 'sensei-course-participants' ),
			'rand'            => esc_html__( 'Random Order', 'sensei-course-participants' ),
		);
	}

	/**
	 * Get an array of the available order options.
	 *
	 * @since  1.0.0
	 * @return array
	 */
	protected function get_order_options () {
		return array(
			'ASC'  => esc_html__( 'Ascending', 'sensei-course-participants' ),
			'DESC' => esc_html__( 'Descending', 'sensei-course-participants' ),
		);
	}

	/**
	 * Get an array of the available display options.
	 *
	 * @since  1.0.0
	 * @return array
	 */
	protected function get_display_options () {
		return array(
			'list' => esc_html__( 'List', 'sensei-course-participants' ),
			'grid' => esc_html__( 'Grid', 'sensei-course-participants' ),
		);
	}
}
