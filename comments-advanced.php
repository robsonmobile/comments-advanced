<?php
/*
Plugin Name: Comments-advanced
Plugin URI: http://wordpress.org/plugins/comments-advanced/
Description: Edit comment's info: Post ID, Parent Comment ID, User ID, Author IP, Author Agent and Comment Date.
Version: 2.0
Author: webvitaly
Author URI: http://web-profile.net/wordpress/plugins/
License: GPLv3
*/

function comments_advanced_unqprfx_add_meta() {
	add_meta_box( 'comment-info', 'Comment advanced info', 'comments_advanced_unqprfx_meta', 'comment', 'normal' );
}
add_action('admin_menu', 'comments_advanced_unqprfx_add_meta');

function comments_advanced_unqprfx_meta() {
	global $wpdb, $comment;
?>

<table class="widefat" cellspacing="0">
<tbody>
	<tr class="alternate">
		<td class="textright">
			<label for="comment_post_id">Post ID</label>
		</td>
		<td>
<?php
	$html = '';
	$posts_list = $wpdb->get_results( "SELECT `ID`, `post_title` FROM `" . $wpdb->prefix . "posts` WHERE `post_status` = 'publish' AND ( `post_type` = 'post' OR `post_type` = 'page' ) ORDER BY `ID` DESC;", ARRAY_A );
	if ( ! empty( $posts_list ) ) {
		foreach ( $posts_list as $post ) {
			$selected = '';
			if ( ! empty( $comment->comment_post_ID ) && $post['ID'] == $comment->comment_post_ID ) {
				$selected = ' selected="selected"';
			}
			
			$post_title = trim( wp_strip_all_tags( $post['post_title'] ) );
			if( empty( $post_title ) ) {
				$post_title = '[Empty title]';
			}
			
			if (mb_strlen($post_title) > 50) {
				$post_title = mb_substr($post_title, 0, 50, 'UTF-8').'...';
			}
			$html .= '<option value="' . esc_attr($post['ID']) . '"' . $selected . '>[' . $post['ID'] . '] ' . $post_title . '</option>';
		}
	} else { // No posts found
		$html = '<option>No posts found</option>';
	}
?>
			<select name="comment_post_id" id="comment_post_id">
				<?php echo $html; ?>
			</select>
			<div>Legend: [Post ID] Post title</div>
		</td>
	</tr>
	<tr>
		<td class="textright">
			<label for="comment_parent">Parent Comment ID</label>
		</td>
		<td>
<?php
	$html = '';
	$comments_list = get_comments( array( 
		'post_id' => $comment->comment_post_ID, 
		'comment_approved' => 1 
	) );

	foreach ( $comments_list as $comment_item ) {
		if ( $comment_item->comment_ID != $comment->comment_ID && $comment_item->comment_ID < $comment->comment_ID ) { // hide himself and later
			$selected = '';
			if ( $comment_item->comment_ID == $comment->comment_parent ) {
				$selected = ' selected="selected"';
			}
			
			$comment_content = trim( wp_strip_all_tags( $comment_item->comment_content ) );
			if( empty( $comment_content ) ) {
				$comment_content = '[Empty comment]';
			}
			
			if (mb_strlen($comment_content) > 50) {
				$comment_content = mb_substr(wp_strip_all_tags($comment_item->comment_content), 0, 50, 'UTF-8') . '...';
			}
			$html .= '<option value="'.esc_attr($comment_item->comment_ID).'"' . $selected . '>';
			$html .= '['.$comment_item->comment_ID.'] ['.$comment_item->comment_author.'] '.$comment_content.'</option>';
		}
	}
?>
			<select name="comment_parent" id="comment_parent">
				<option value='0'>[0] [Without parent comment]</option>
				<?php echo $html; ?>
			</select>
			<div>Legend: [Comment ID] [Comment Author] Comment content</div>
		</td>
	</tr>
	<tr class="alternate">
		<td class="textright">
			<label for="comment_user_id">User ID</label>
		</td>
		<td>
<?php
	$html = '';
	$users_list = $wpdb->get_results( "SELECT " . $wpdb->prefix . "users.ID, " . $wpdb->prefix . "users.display_name, " . $wpdb->prefix . "usermeta.meta_value FROM " . $wpdb->prefix . "users
	 JOIN " . $wpdb->prefix . "usermeta ON " . $wpdb->prefix . "users.ID = " . $wpdb->prefix . "usermeta.user_id WHERE " . $wpdb->prefix . "usermeta.meta_key = 'wp_capabilities'
	 ORDER BY " . $wpdb->prefix . "users.ID;", ARRAY_A );
	
	foreach ( $users_list as $user_item ) {
		$selected = '';
		if ( $user_item['ID'] == $comment->user_id ) {
			$selected = ' selected="selected"';
		}
		
		$user_role = '';
		$user_role_array = unserialize($user_item['meta_value']);
		foreach($user_role_array AS $key => $item) {
			$user_role = $key;
		}
		
		$html .= '<option value="'.esc_attr($user_item['ID']).'"'.$selected.'>';
		$html .= '['.$user_item['ID'].'] ['.$user_role.'] '.$user_item['display_name'].'</option>';
	}
?>
			<select name="comment_user_id" id="comment_user_id">
				<option value='0'>[0] [Without role] Guest</option>
				<?php echo $html; ?>
			</select>
			<div>Legend: [User ID] [User Role] Username</div>
		</td>
	</tr>
	<tr>
		<td class="textright">
			<label for="comment_author_ip">Author IP</label>
		</td>
		<td>
			<input type="text" name="comment_author_ip" id="comment_author_ip" value="<?php echo esc_attr( $comment->comment_author_IP ); ?>" size="40" />
		</td>
	</tr>
	<tr class="alternate">
		<td class="textright">
			<label for="comment_agent">Author Agent</label>
		</td>
		<td>
			<input type="text" name="comment_agent" id="comment_agent" value="<?php echo esc_attr( $comment->comment_agent ); ?>" size="40" />
		</td>
	</tr>
	<tr>
		<td class="textright">
			<label for="comment_date">Comment Date</label>
		</td>
		<td>
			<input type="text" name="comment_date" id="comment_date" value="<?php echo esc_attr( $comment->comment_date ); ?>" size="40" />
		</td>
	</tr>
</tbody>
</table>


<?php
}

function comments_advanced_unqprfx_save_meta($comment_ID) {
	global $wpdb;

	$comment_post_ID = absint( $_POST['comment_post_id'] );
	$comment_parent = absint( $_POST['comment_parent'] );
	$user_id = absint( $_POST['comment_user_id'] );
	$comment_author_IP = esc_attr( $_POST['comment_author_ip'] );
	$comment_agent = esc_attr( $_POST['comment_agent'] );
	$comment_date = esc_attr( $_POST['comment_date'] );

	if ($comment_parent == $comment_ID) { // comment parent cannot be self
		return false; // don't update
	}

	$post = get_post($comment_post_ID); // check if post exist
	if ( !$post ) {
		return false; // don't update
	}

	$comment_row = $wpdb->get_row( $wpdb->prepare( "select * from $wpdb->comments where comment_ID = %s", $comment_ID ) );
	$old_comment_post_ID = $comment_row->comment_post_ID; // get old comment_post_ID

	if( $old_comment_post_ID != $comment_post_ID ){ // if comment_post_ID was updated
		wp_update_comment_count( $old_comment_post_ID ); // we need to update comment counts for both posts (old and new)
		wp_update_comment_count( $comment_post_ID );
		$comment_parent = "0"; // reset comment_parent if comment was moved to another post
	}
	
	$wpdb->update(
		$wpdb->comments,
		array(
			'comment_post_ID' => $comment_post_ID,
			'comment_parent' => $comment_parent,
			'user_id' => $user_id,
			'comment_author_IP' => $comment_author_IP,
			'comment_agent' => $comment_agent,
			'comment_date' => $comment_date
		),
		array( 'comment_ID' => $comment_ID )
	);
}
add_action('edit_comment', 'comments_advanced_unqprfx_save_meta');


function comments_advanced_unqprfx_plugin_meta( $links, $file ) { // add links to plugin meta row
	if ( $file == plugin_basename( __FILE__ ) ) {
		$row_meta = array(
			'support' => '<a href="http://web-profile.net/wordpress/plugins/comments-advanced/" target="_blank">Comments-advanced</a>',
			'donate' => '<a href="http://web-profile.net/donate/" target="_blank">Donate</a>',
			'pro' => '<a href="http://codecanyon.net/item/silver-bullet-pro/15171769?ref=webvitalii" target="_blank" title="Speedup and protect WordPress in a smart way">Silver Bullet Pro</a>'
		);
		$links = array_merge( $links, $row_meta );
	}
	return (array) $links;
}
add_filter( 'plugin_row_meta', 'comments_advanced_unqprfx_plugin_meta', 10, 2 );
