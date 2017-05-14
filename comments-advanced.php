<?php
/*
Plugin Name: Comments-advanced
Plugin URI: http://wordpress.org/plugins/comments-advanced/
Description: Edit comment's info: post id, parent comment id, user id, author IP, author agent.
Version: 1.1
Author: webvitaly
Author URI: http://web-profile.com.ua/wordpress/plugins/
License: GPLv3
*/

function comments_advanced_unqprfx_add_meta() {
	add_meta_box( 'comment-info', 'Comment advanced info', 'comments_advanced_unqprfx_meta', 'comment', 'normal' );
}
add_action('admin_menu', 'comments_advanced_unqprfx_add_meta');

function comments_advanced_unqprfx_meta() {
	global $comment;
?>

<table class="widefat" cellspacing="0">
<tbody>
	<tr class="alternate">
		<td class="textright">
			<label for="comment_post_id">post id</label>
		</td>
		<td>
			<input type="text" name="comment_post_id" id="comment_post_id" value="<?php echo esc_attr( $comment->comment_post_ID ); ?>" size="40" />
		</td>
	</tr>
	<tr>
		<td class="textright">
			<label for="comment_parent">parent id</label>
		</td>
		<td>
			<input type="text" name="comment_parent" id="comment_parent" value="<?php echo esc_attr( $comment->comment_parent ); ?>" size="40" />
		</td>
	</tr>
	<tr class="alternate">
		<td class="textright">
			<label for="comment_user_id">user id</label>
		</td>
		<td>
			<input type="text" name="comment_user_id" id="comment_user_id" value="<?php echo esc_attr( $comment->user_id ); ?>" size="40" />
		</td>
	</tr>
	<tr>
		<td class="textright">
			<label for="comment_author_ip">author IP</label>
		</td>
		<td>
			<input type="text" name="comment_author_ip" id="comment_author_ip" value="<?php echo esc_attr( $comment->comment_author_IP ); ?>" size="40" />
		</td>
	</tr>
	<tr class="alternate">
		<td class="textright">
			<label for="comment_agent">author agent</label>
		</td>
		<td>
			<input type="text" name="comment_agent" id="comment_agent" value="<?php echo esc_attr( $comment->comment_agent ); ?>" size="40" />
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

	if ($comment_parent == $comment_ID) { // comment parent cannot be self
		return false; // don't update
	}

	$post = get_post($comment_post_ID); // check if post exist
	if ( !$post ) {
		return false; // don't update
	}

	$comment_row = $wpdb->get_row( $wpdb->prepare( "select * from $wpdb->comments where comment_ID = %s", $comment_ID ) );
	$old_comment_post_ID = $comment_row->comment_post_ID; // get old comment_post_ID

	$wpdb->update(
		$wpdb->comments,
		array(
			'comment_post_ID' => $comment_post_ID,
			'comment_parent' => $comment_parent,
			'user_id' => $user_id,
			'comment_author_IP' => $comment_author_IP,
			'comment_agent' => $comment_agent
		),
		array( 'comment_ID' => $comment_ID )
	);

	if( $old_comment_post_ID != $comment_post_ID ){ // if comment_post_ID was updated
		wp_update_comment_count( $old_comment_post_ID ); // we need to update comment counts for both posts (old and new)
		wp_update_comment_count( $comment_post_ID );
	}

}
add_action('edit_comment', 'comments_advanced_unqprfx_save_meta');


function comments_advanced_unqprfx_plugin_meta( $links, $file ) { // add 'Plugin page' and 'Donate' links to plugin meta row
	if ( strpos( $file, 'comments-advanced.php' ) !== false ) {
		$links = array_merge( $links, array( '<a href="http://web-profile.com.ua/wordpress/plugins/comments-advanced/" title="Plugin page">Comments-advanced</a>' ) );
		$links = array_merge( $links, array( '<a href="http://web-profile.com.ua/donate/" title="Support the development">Donate</a>' ) );
	}
	return $links;
}
add_filter( 'plugin_row_meta', 'comments_advanced_unqprfx_plugin_meta', 10, 2 );
