# Like/Dislike Post and Comment
WP Like/Dislike Post and Comment is a powerful and feature-rich WordPress plugin that lets you add like and dislike functionality to posts, comments, BuddyPress activities, and bbPress topics. It also includes detailed analytics, custom templates, auto-like features, and integration with user profiles.

Features:
Supports Posts, Comments, bbPress, BuddyPress
Real-time analytics and statistics
Custom templates and styling
GDPR compliant


Note: Add this code to theme function.php file

function ld_enqueue_assets() {
    wp_enqueue_script(
        'ld-script',
        plugin_dir_url(__FILE__) . 'assets/script.js',
        array('jquery'),
        null,
        true
    );
 
    wp_localize_script('ld-script', 'ld_ajax_object', array(
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce'    => wp_create_nonce('ld_nonce')
    ));
}
add_action('wp_enqueue_scripts', 'ld_enqueue_assets');
add_action('wp_ajax_ld_handle_vote', 'ld_handle_vote_callback');
add_action('wp_ajax_nopriv_ld_handle_vote', 'ld_handle_vote_callback');
 
function ld_handle_vote_callback() {
    check_ajax_referer('ld_nonce', 'nonce');
 
    $item_id = intval($_POST['item_id']);
    $type = sanitize_text_field($_POST['type']);
    $action_type = sanitize_text_field($_POST['action_type']);
 
    $like_key = $type . '_likes';
    $dislike_key = $type . '_dislikes';
 
    $likes = (int) get_post_meta($item_id, $like_key, true);
    $dislikes = (int) get_post_meta($item_id, $dislike_key, true);
 
    if ($action_type === 'like') {
        $likes++;
        update_post_meta($item_id, $like_key, $likes);
    } elseif ($action_type === 'dislike') {
        $dislikes++;
        update_post_meta($item_id, $dislike_key, $dislikes);
    } else {
        wp_send_json_error(['message' => 'Invalid action.']);
    }
 
    wp_send_json_success([
        'likes' => $likes,
        'dislikes' => $dislikes,
    ]);
}