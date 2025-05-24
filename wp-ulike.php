<?php
/**
 * Plugin Name: Like Dislike Plugin
 * Description: Allows logged-in users to like/dislike custom post types and comments.
 * Version: 1.0
 * Author: Your Name
 */

if (!defined('ABSPATH')) exit;

class LikeDislikePlugin {

    public function __construct() {
        add_action('wp_enqueue_scripts', [$this, 'enqueue_scripts']);
        add_action('wp_ajax_handle_vote', [$this, 'handle_vote']);
        add_action('wp_ajax_nopriv_handle_vote', [$this, 'block_guests']);

        add_filter('the_content', [$this, 'append_buttons_to_post']);
        add_filter('comment_text', [$this, 'append_buttons_to_comment']);
    }

    public function enqueue_scripts() {
        wp_enqueue_script('like-dislike-script', plugin_dir_url(__FILE__) . 'assets/script.js', ['jquery'], null, true);
        wp_localize_script('like-dislike-script', 'ld_ajax_obj', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce'    => wp_create_nonce('ld_nonce')
        ]);
    }

    public function block_guests() {
        wp_send_json_error(['message' => 'Login required.']);
    }

public function handle_vote() {
    check_ajax_referer('ld_nonce', 'nonce');

    if (!is_user_logged_in()) {
        wp_send_json_error(['message' => 'Login required.']);
    }

    $user_id = get_current_user_id();
    $item_id = intval($_POST['item_id']);
    $type = sanitize_text_field($_POST['type']); // 'post' or 'comment'
    $action = sanitize_text_field($_POST['action_type']); // 'like' or 'dislike'

    $like_key = "_ld_like_{$type}_users";
    $dislike_key = "_ld_dislike_{$type}_users";

    $like_count_key = "_ld_like_{$type}_count";
    $dislike_count_key = "_ld_dislike_{$type}_count";

    $like_users = get_metadata($type, $item_id, $like_key, true);
    $dislike_users = get_metadata($type, $item_id, $dislike_key, true);
    $like_users = is_array($like_users) ? $like_users : [];
    $dislike_users = is_array($dislike_users) ? $dislike_users : [];

    // Remove user from both if already voted
    $already_liked = in_array($user_id, $like_users);
    $already_disliked = in_array($user_id, $dislike_users);

    if ($already_liked) {
        $like_users = array_diff($like_users, [$user_id]);
        update_metadata($type, $item_id, $like_key, $like_users);
        update_metadata($type, $item_id, $like_count_key, count($like_users));
    }

    if ($already_disliked) {
        $dislike_users = array_diff($dislike_users, [$user_id]);
        update_metadata($type, $item_id, $dislike_key, $dislike_users);
        update_metadata($type, $item_id, $dislike_count_key, count($dislike_users));
    }

    // Apply new vote if not already the same
    if (($action === 'like' && !$already_liked) || ($action === 'dislike' && !$already_disliked)) {
        if ($action === 'like') {
            $like_users[] = $user_id;
            update_metadata($type, $item_id, $like_key, $like_users);
            update_metadata($type, $item_id, $like_count_key, count($like_users));
        } else {
            $dislike_users[] = $user_id;
            update_metadata($type, $item_id, $dislike_key, $dislike_users);
            update_metadata($type, $item_id, $dislike_count_key, count($dislike_users));
        }
    }

    wp_send_json_success([
        'likes' => count($like_users),
        'dislikes' => count($dislike_users)
    ]);
}


    public function render_buttons($id, $type) {
        $like_count = (int)get_metadata($type, $id, "_ld_like_{$type}_count", true);
        $dislike_count = (int)get_metadata($type, $id, "_ld_dislike_{$type}_count", true);

        ob_start();
        ?>
<div class="ld-buttons" data-id="<?php echo $item_id; ?>" data-type="<?php echo $type; ?>">
    <button class="ld-button ld-like"><img draggable="false" role="img" class="emoji" alt="👍" src="https://s.w.org/images/core/emoji/15.0.3/svg/1f44d.svg" width="20" height="20">Like (<span class="ld-like-count"><?php echo $like_count; ?></span>) </button>
    <button class="ld-button ld-dislike"><img draggable="false" role="img" class="emoji" alt="👎" src="https://s.w.org/images/core/emoji/15.0.3/svg/1f44e.svg" width="20" height="20">Dislike (<span class="ld-dislike-count"><?php echo $dislike_count; ?></span>)</button>
</div>


        <?php
        return ob_get_clean();
    }

    public function append_buttons_to_post($content) {
        if (is_singular('video_tutorial')) {
            global $post;
            return $content . $this->render_buttons($post->ID, 'post');
        }
        return $content;
    }

    public function append_buttons_to_comment($comment_text) {
        $comment_id = get_comment_ID();
        return $comment_text . $this->render_buttons($comment_id, 'comment');
    }
}

new LikeDislikePlugin();
