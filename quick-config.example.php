<?php
/**
 * QuickWP v2 - Main Configuration
 * 
 * SETUP INSTRUCTIONS:
 * 1. Copy this file to: quick-config.php
 * 2. Edit the values below with your WordPress site details
 * 3. Never commit quick-config.php to version control!
 */

return [
    // ===========================================
    // REST API ENDPOINTS
    // ===========================================
    // Your WordPress site REST API endpoints
    'posts_endpoint' => 'https://your-site.com/wp-json/wp/v2/posts',
    'pages_endpoint' => 'https://your-site.com/wp-json/wp/v2/pages',
    'media_endpoint' => 'https://your-site.com/wp-json/wp/v2/media',

    // Optional - if empty, will be derived from posts_endpoint
    'categories_endpoint' => '',
    'tags_endpoint'       => '',

    // Default CPT slug for the Custom Post Type tool
    'cpt_default_slug' => 'post',

    // ===========================================
    // PAGE & POST TEMPLATES
    // ===========================================
    // Define available templates for quick selection
    // Format: 'template-file.php' => 'Display Name'
    // Leave empty to use text input instead of dropdown
    
    'page_templates' => [
        ''                      => '— Default Template —',
        'template-full-width.php'    => 'Full Width (No Sidebar)',
        'template-blank.php'         => 'Blank / Canvas',
        'template-landing.php'       => 'Landing Page',
        'template-sidebar-left.php'  => 'Sidebar Left',
        'template-sidebar-right.php' => 'Sidebar Right',
        'template-contact.php'       => 'Contact Page',
        'template-about.php'         => 'About Page',
    ],
    
    // Post templates (WordPress 4.7+ feature)
    // Many themes don't support this - leave empty if not needed
    'post_templates' => [
        ''                      => '— Default Template —',
        'single-full-width.php'      => 'Full Width Post',
        'single-no-sidebar.php'      => 'No Sidebar',
        'single-featured.php'        => 'Featured Post',
    ],

    // ===========================================
    // WORDPRESS CREDENTIALS
    // ===========================================
    // Create an Application Password in WordPress:
    // Users -> Your Profile -> Application Passwords
    'wp_username'     => 'your-wp-username',
    'wp_app_password' => 'xxxx xxxx xxxx xxxx xxxx xxxx',

    // Show auth fields in the web forms?
    // true = show username/password inputs in forms
    // false = hide fields, use only the values above
    'show_auth_form' => true,

    // ===========================================
    // SSL & DEBUG
    // ===========================================
    // Keep true in production. Set false only for self-signed certs.
    'verify_ssl' => true,

    // Show extra debug info on errors
    'debug_http' => false,

    // ===========================================
    // ACCESS PROTECTION FOR THIS TOOL
    // ===========================================
    // Protect these tools from unauthorized access
    // 
    // 'none'  = no protection (be careful!)
    // 'basic' = HTTP Basic Auth (uses access_basic_user/password)
    // 'token' = URL token (use ?token=your-token)
    'access_mode' => 'none',

    // For access_mode = 'basic'
    'access_basic_user'     => 'admin',
    'access_basic_password' => 'change-this-password',

    // For access_mode = 'token'
    'access_token' => 'set-a-long-random-token-here',
];
