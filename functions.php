<?php
// TehseenRaza Premium - functions.php
// Guest form, auto-author, E-E-A-T, monetization — nothing else

// 1. Enqueue JS/CSS
function tr_enqueue_assets() {
    wp_enqueue_style('tr-style', get_stylesheet_uri());
    wp_enqueue_script('tr-js', get_template_directory_uri() . '/js/script.js', ['jquery'], '1.0', true);
    wp_localize_script('tr-js', 'tr_ajax', [
        'ajaxurl' => admin_url('admin-ajax.php'),
        'nonce'   => wp_create_nonce('tr_guest')
    ]);
}
add_action('wp_enqueue_scripts', 'tr_enqueue_assets');

// 2. Custom Post Type: guest_article
function tr_register_cpt() {
    register_post_type('guest_article', [
        'public'       => true,
        'has_archive'  => true,
        'rewrite'      => ['slug' => 'contributions'],
        'supports'     => ['title', 'editor', 'author', 'excerpt', 'thumbnail'],
        'menu_icon'    => 'dashicons-welcome-write-blog',
        'show_in_rest' => true
    ]);
}
add_action('init', 'tr_register_cpt');

// 3. E-E-A-T Fields in User Profile
function tr_add_contact_methods($methods) {
    $methods['linkedin'] = 'LinkedIn URL';
    $methods['expert_bio'] = 'Expert Bio';
    return $methods;
}
add_filter('user_contactmethods', 'tr_add_contact_methods');

// 4. Guest Form Shortcode
function tr_guest_form() {
    ob_start();
    ?>
    <form id="tr-guest-form" class="tr-guest-form">
        <input type="text" name="title" placeholder="Article Title" required><br>
        <textarea name="content" placeholder="Content" rows="8" required></textarea><br>
        <input type="text" name="name" placeholder="Your Name" required><br>
        <input type="email" name="email" placeholder="Your Email" required><br>
        <input type="hidden" name="nonce" value="<?php echo wp_create_nonce('tr_guest'); ?>">
        <button type="submit">Submit</button>
    </form>
    <div id="response"></div>
    <?php
    return ob_get_clean();
}
add_shortcode('tr_guest_form', 'tr_guest_form');

// 5. AJAX Submit
function tr_submit_guest_post() {
    check_ajax_referer('tr_guest', 'nonce');
    
    $title = sanitize_text_field($_POST['title']);
    $content = wp_kses_post($_POST['content']);
    $name = sanitize_text_field($_POST['name']);
    $email = sanitize_email($_POST['email']);

    $post_id = wp_insert_post([
        'post_title'   => $title,
        'post_content' => $content,
        'post_type'    => 'guest_article',
        'post_status'  => 'pending'
    ]);

    if ($post_id) {
        update_post_meta($post_id, '_guest_name', $name);
        update_post_meta($post_id, '_guest_email', $email);
        wp_send_json_success('Submitted! Awaiting review.');
    } else {
        wp_send_json_error('Failed.');
    }
}
add_action('wp_ajax_tr_submit', 'tr_submit_guest_post');
add_action('wp_ajax_nopriv_tr_submit', 'tr_submit_guest_post');

// 6. Admin Notice on Pending Post
function tr_pending_notice() {
    global $post;
    if (!$post || $post->post_type !== 'guest_article' || $post->post_status !== 'pending') return;

    $name = get_post_meta($post->ID, '_guest_name', true);
    $email = get_post_meta($post->ID, '_guest_email', true);
    if (!$name || !$email) return;
    ?>
    <div class="notice notice-warning">
        <p><strong>New Guest:</strong> <?php echo esc_html($name); ?> (<?php echo esc_html($email); ?>)<br>
        <a href="<?php echo admin_url('user-new.php'); ?>">Create Account</a></p>
    </div>
    <?php
}
add_action('admin_notices', 'tr_pending_notice');

// 7. Auto-Create Author on Publish
function tr_auto_create_author($new_status, $old_status, $post) {
    if ($post->post_type !== 'guest_article' || $new_status !== 'publish' || $old_status === 'publish') return;

    $name = get_post_meta($post->ID, '_guest_name', true);
    $email = get_post_meta($post->ID, '_guest_email', true);

    if (!$name || !$email) return;

    $user_id = get_post_meta($post->ID, '_guest_user_id', true);
    if ($user_id) {
        wp_update_post(['ID' => $post->ID, 'post_author' => $user_id]);
        return;
    }

    $user_id = wp_create_user($name, wp_generate_password(), $email);
    if (!is_wp_error($user_id)) {
        wp_update_user(['ID' => $user_id, 'role' => 'contributor', 'display_name' => $name]);
        update_post_meta($post->ID, '_guest_user_id', $user_id);
        wp_update_post(['ID' => $post->ID, 'post_author' => $user_id]);
        wp_mail($email, 'Article Published!', 'Your article is live: ' . get_permalink($post->ID));
    }
}
add_action('transition_post_status', 'tr_auto_create_author', 10, 3);

// 8. Sponsored Checkbox
function tr_add_sponsored_box() {
    add_meta_box('tr_sponsored', 'Sponsored', 'tr_sponsored_cb', ['post', 'guest_article'], 'side');
}
add_action('add_meta_box', 'tr_add_sponsored_box');

function tr_sponsored_cb($post) {
    echo '<label><input type="checkbox" name="tr_sponsored" ' . checked(get_post_meta($post->ID, '_tr_sponsored', true), 'yes', false) . '> Sponsored</label>';
}

function tr_save_sponsored($post_id) {
    if (!current_user_can('edit_post', $post_id)) return;
    update_post_meta($post_id, '_tr_sponsored', isset($_POST['tr_sponsored']) ? 'yes' : 'no');
}
add_action('save_post', 'tr_save_sponsored');

// 9. Sponsored Bar
function tr_show_sponsored_bar() {
    if (is_single() && get_post_meta(get_the_ID(), '_tr_sponsored', true) === 'yes') {
        echo '<div style="background:#ffeb3b;color:#000;padding:10px;text-align:center;font-weight:bold;margin:10px 0;">📢 SPONSORED CONTENT</div>';
    }
}
add_action('wp_head', 'tr_show_sponsored_bar');

// 10. Author Box
function tr_author_box() {
    if (!is_single()) return;
    $linkedin = get_the_author_meta('linkedin');
    $bio = get_the_author_meta('expert_bio');
    ?>
    <div class="tr-author-box">
        <img src="<?php echo get_avatar_url(get_the_author_meta('ID')); ?>" width="60">
        <strong><?php the_author(); ?></strong><br>
        <?php if ($bio) echo esc_html($bio) . '<br>'; ?>
        <?php if ($linkedin) echo '<a href="' . esc_url($linkedin) . '">LinkedIn</a>'; ?>
    </div>
    <?php
}
add_filter('the_content', function($content) {
    if (is_single()) $content .= tr_author_box();
    return $content;
});