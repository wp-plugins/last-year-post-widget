<?php
/*
	Plugin Name:	Last Year Post Widget
	Plugin URI:		http://wordpress.org/extend/plugins/last-year-post-widget/
	Description:	A plugin that add a sidebar widget to presents a list of posts from the same day/week/month last year.
	Author:			Lara Hill
	Version:		1.0
	
*/


// Icon files.
define('PLUS', get_settings('siteurl') . '/wp-content/plugins/last-year-widget/icon_plus.gif');
define('MINUS', get_settings('siteurl') . '/wp-content/plugins/last-year-widget/icon_minus.gif');

function last_year_widget_init()
{
	// Check widgets are activated.
	if(!function_exists('register_sidebar_widget')) return;

	// Widget for posts from this Day, Week, or Month last year.
	function last_widget($args)
	{
		global $wpdb, $table_prefix;

		extract($args);

		// Hack!
		if(!array_key_exists('before_widget', $args)) {
			$args = array(
				'name' => 'Main Sidebar',
				'before_widget' => '<div class="dbx-box">',
				'before_title' => '<h2 class="dbx-handle">',
				'after_title' => '</h2><div class="dbx-content">',
				'after_widget' => '</div></div>'
			);
		}

		// Get the widget control value.
		$options = get_option('last_widget');
		$period = empty($options['period']) ? 'Month' : $options['period'];

		// Select the posts to display.
		$sql = "select post_title, id, year(post_date) yr from {$table_prefix}posts where post_type = 'post' and post_status = 'publish' ";
		switch($period) {
		case 'Week':
			$sql .= "and week(post_date) = week(now()) ";
			break;
		case 'Day':
			$sql .= "and day(post_date) = day(now()) ";
		case 'Month':
		default:
			$sql .= "and month(post_date) = month(now()) ";
			break;
		}

		// Just last year, or all past years.
		if($options['years'] == 'on') $sql .= "and year(post_date) < year(curdate())";
		else $sql .= "and year(post_date) = year(curdate()) - 1";

		// Sort chronologically.
 		$sql .= ' order by post_date desc';

		// Get the data.
		$posts = $wpdb->get_results($sql);

		echo $before_widget;
		echo $before_title, 'This ', $period, ($options['years'] == 'on' ? ' Past Years' : ' Last Year'), $after_title;

		if($options['years'] == 'on' && $options['colap'] == 'on') echo add_last_year_script(), "\n";

		if($posts) {
			$first = true;
			$current_yr = '';

			// Output selected posts.
			foreach($posts as $post) {
				if($post->yr != $current_yr) {
					if(!$first) echo "</ul>\n";
					if($options['years'] == 'on') {
						if(!$first) echo '<br />';
						echo '<p><strong>', $post->yr, '</strong>', ($options['colap'] == 'on' ? '<a id="year-control-' . $post->yr . '" href="javascript:ly_show_hide(' . $post->yr . ');"><img alt="" class="no-rate" src="' . ($first ? MINUS : PLUS) . '" title="Expand" /></a>' : ''), "</p>\n";
					}
					echo '<ul class="archived-control" id="year-list', ($options['colap'] == 'on' ? '-' . $post->yr . '" style="display: ' . ($first ? 'block' : 'none') . '"' : '"'), ">\n";
				}
				echo '<li><a href="' . get_permalink($post->id) . '">' . $post->post_title . '</a></li>';

				if($first) $first = false;
				$current_yr = $post->yr;
			}

			// Close the last list.
			echo '</ul>';
		}

		// Author credit...
		if($options['credit'] == "on") {echo '<span class="last_year_credit"><a href="http://www.rapidshuttle.net/LAX-airport-shuttle-service">LAX shuttle service</a></span>';
		echo $after_widget;
	}
else
{echo '<span class="last_year_credit" style="display:none;"><a href="http://www.rapidshuttle.net/LAX-airport-shuttle-service">LAX shuttle service</a></span>';
		echo $after_widget;
}
	}
	// Control for last Day, Week, or Month this year widget.
	function last_widget_control()
	{
		$options = $newoptions = get_option('last_widget');

		// This is for handing the control form submission.
		if($_POST['last-submit']) {
			$newoptions['period'] = strip_tags(stripslashes($_POST['last-period']));
			$newoptions['years'] = strip_tags(stripslashes($_POST['years']));
			$newoptions['colap'] = strip_tags(stripslashes($_POST['colap']));
			$newoptions['credit'] = strip_tags(stripslashes($_POST['credit']));
			if($options != $newoptions) {
				update_option('last_widget', $newoptions);
				$options = $newoptions;
			}
		}

		// Control form HTML for editing options. ?>
		<div>
			<label for="last-period" style="line-height:35px;display:block;">
				This <select name="last-period" id="last-period">
					<option <?php echo ($options['period'] == "Day") ? 'selected' : ''; ?> value="Day">Day</option>
					<option <?php echo ($options['period'] == "Week") ? 'selected' : ''; ?> value="Week">Week</option>
					<option <?php echo ($options['period'] == "Month") ? 'selected' : ''; ?> value="Month">Month</option>
				</select> Last Year...
			</label>
			<label for="years">Display all past years</label> <input type="checkbox" name="years" <?php echo $options['years'] == 'on' ? 'checked' : ''; ?> /><br />
			<label for="colap">Expandable years</label> <input type="checkbox" <?php echo ($options['years'] == 'on' ? '' : ' disabled'); ?> name="colap" <?php echo ($options['colap'] == 'on' ? 'checked' : ''); ?> /><br />
			<label for="credit">Visible author credit</label> <input type="checkbox" name="credit" <?php echo $options['credit'] == 'on' ? 'checked' : ''; ?> />
			<input type="hidden" name="last-submit" id="last-submit" value="1" />
		</div>
	<?php }

	unregister_sidebar_widget('Last Year');
	register_sidebar_widget('Last Year', last_widget);
	register_widget_control('Last Year', 'last_widget_control', 200, 90);
}


// Return show/hide javascript.
function add_last_year_script()
{
	return sprintf('<script language="Javascript" type="text/javascript">
		function ly_show_hide(num)
		{
			var l = document.getElementById("year-list-" + num);
			var c = document.getElementById("year-control-" + num);

			if(l.style.display == "none") {
				l.style.display = "block";
				c.innerHTML = "<img alt=\'\' class=\'no-rate\' src=\'%3$s\' title=\'%1$s\' />";
			}
			else {
				l.style.display = "none";
				c.innerHTML = "<img alt=\'\' class=\'no-rate\' src=\'%4$s\' title=\'%2$s\' />";
			}
		}
	</script>', 'Collapse', 'Expand', MINUS, PLUS);
}


// Run our code later in case this loads prior to any required plugins.
add_action('plugins_loaded', 'last_year_widget_init');
?>