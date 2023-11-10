<?php
/*
Plugin Name: Custom Blog API
Version: 1.0
Description: Custom APIs for blog functionality.
*/

//register routes
function custom_blog_api_routes() {
    
    register_rest_route('custom-blog-api/v1', '/register', array(
        'methods' => 'POST',
        'callback' => 'custom_register_user',
    ));

    register_rest_route('custom-blog-api/v1', '/login', array(
        'methods' => 'POST',
        'callback' => 'custom_user_login',
    ));

    register_rest_route('custom-blog-api/v1', '/create-post', array(
        'methods' => 'POST',
        'callback' => 'custom_create_blog_post',
    ));

    register_rest_route('custom-blog-api/v1', '/post-comment', array(
        'methods' => 'POST',
        'callback' => 'custom_post_comment',
    ));
}

add_action('rest_api_init', 'custom_blog_api_routes');

function custom_register_user($data) {
    global $wpdb;

    $username = sanitize_user($data['username']);
    $email = sanitize_email($data['email']);
    $password = $data['password'];

    // Email validation
    if (!is_email($email)) {
        return new WP_Error('registration_error', 'Invalid email format.', array('status' => 400));
    }

    // Check for duplicate email or username
    if (email_exists($email) || username_exists($username)) {
        return new WP_Error('registration_error', 'Email or username already exists.', array('status' => 400));
    }

    // Create user
    $user_id = wp_create_user($username, $password, $email);

    // Insert into custom db
    $wpdb->insert('registereduser', array(
        'username' => $username,
        'email' => $email,
        'password' => $password,
    ));

    if ($wpdb->last_error) {
        return new WP_Error('database_error', 'Database error: ' . $wpdb->last_error, array('status' => 500));
    }

    return array('message' => 'User registered successfully.', 'user_id' => $user_id);
}

function custom_user_login($data) {
    global $wpdb;

    $email = sanitize_email($data['email']);
    $password = $data['password'];

    //validate user
    $user = wp_authenticate($email, $password);

    if (is_wp_error($user)) {
        return new WP_Error('login_error', 'Invalid credentials.', array('status' => 401));
    }

    return array('message' => 'Login successful.', 'user_id' => $user->ID);
}

function custom_create_blog_post($data) {
    global $wpdb;

    // Ensure the user is logged in
    $user_id = get_current_user_id();
    if (!$user_id) {
        return new WP_Error('unauthorized', 'Unauthorized. Please log in.', array('status' => 401));
    }

    $title = sanitize_text_field($data['title']);
    $content = wp_kses_post($data['content']);

    // Insert into custom db
    $wpdb->insert('blog', array(
        'user_id' => $user_id,
        'title' => $title,
        'content' => $content,
    ));

    return array('message' => 'Blog post created successfully.');
}

function custom_post_comment($data) {
    global $wpdb;

    // Ensure the user is logged in
    $user_id = get_current_user_id();
    if (!$user_id) {
        return new WP_Error('unauthorized', 'Unauthorized. Please log in.', array('status' => 401));
    }

    $post_id = (int) $data['post_id'];
    $comment_content = wp_kses_post($data['comment']);

    // Insert into custom db 
    $wpdb->insert('comments', array(
        'user_id' => $user_id,
        'post_id' => $post_id,
        'comment_content' => $comment_content,
    ));

    return array('message' => 'Comment posted successfully.');
}

