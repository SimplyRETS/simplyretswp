<?php

/*
 *
 * simply-rets-widgets.php - Copyright (C) Reichert Brothers 2014
 * This file provides the logic for the simply-rets sidebar widgets.
 *
*/

/*
 * To add new widgets, extend the WP_Widget class  with a constructor,
 * update, form, and widget function.
 * To activate new widgets, simply add a line to register the widget
 * in the srRegisterWidgets function - the rest is already initialized.
*/


/* Code starts here */
function srRegisterWidgets() {
    register_widget('sr_listing_widget');
}


class sr_listing_widget extends WP_Widget {

	/** constructor */
	function sr_listing_widget() {
		parent::WP_Widget(false, $name = 'Simply Rets Listing');
	}

	/** save widget --  @see WP_Widget::update */
	function update( $new_instance, $old_instance ) {
		$instance = $old_instance;
		$instance['title'] = strip_tags($new_instance['title']);
		$instance['mlsid'] = strip_tags($new_instance['mlsid']);
		return $instance;
	}

	/** admin widget form --  @see WP_Widget::form */
	function form( $instance ) {
		$title = esc_attr($instance['title']);
		$mlsid= esc_attr($instance['mlsid']);

		?>
		<p>
		  <label for="<?php echo $this->get_field_id('title'); ?>">
			<?php _e('Title:'); ?>
		  </label>
		  <input class="widefat" id="<?php echo $this->get_field_id('title'); ?>"
				 name="<?php echo $this->get_field_name('title'); ?>"
				 type="text"
				 value="<?php echo $title; ?>" />
		</p>

		<p>
		  <label for="<?php echo $this->get_field_id('mlsid'); ?>">
			<?php _e('Listing MLS Id:'); ?>
		  </label>
		  <input class="widefat"
				 id="<?php echo $this->get_field_id('mlsid'); ?>"
				 name="<?php echo $this->get_field_name('mlsid'); ?>"
				 type="text"
				 value="<?php echo $mlsid; ?>" />
		</p>
		<?php
	}

	/** front end widget render -- @see WP_Widget::widget */
	function widget( $args, $instance ) {
		extract( $args );
		$title = apply_filters('widget_title', $instance['title']);
		$mlsid = $instance['mlsid'];


		$cont .= $before_widget;
		// populate title
		if( $title ) {
			$cont .= $before_title . $title . $after_title;
		} else {
			$cont .= $before_title . "Featured Listing" .$after_title;
		}

		// populate content
		if( $mlsid ) {
			$cont .= SimplyRetsApiHelper::retrieveWidgetListing( $mlsid );
		} else {
			$cont = "No listing found";
		}

		$cont .= $after_widget;
		echo $cont;
	}

}