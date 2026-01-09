<?php
/**
 * QuickWP v2 - Multi-Site Configuration
 * 
 * SETUP INSTRUCTIONS:
 * 1. Copy this file to: quick-sites.php
 * 2. Configure your WordPress sites below
 * 3. Switch between sites using ?site=key in the URL
 * 4. Never commit quick-sites.php to version control!
 * 
 * Each site can override any setting from quick-config.php
 */

return [
    // Which site to use by default
    'default_site' => 'main',

    // Your sites
    'sites' => [
        // Example: Main/Production site
        'main' => [
            'label' => 'Main Site',
            'posts_endpoint' => 'https://your-site.com/wp-json/wp/v2/posts',
            'pages_endpoint' => 'https://your-site.com/wp-json/wp/v2/pages',
            'media_endpoint' => 'https://your-site.com/wp-json/wp/v2/media',
            'wp_username'    => 'your-username',
            'wp_app_password' => 'xxxx xxxx xxxx xxxx xxxx xxxx',
        ],

        // Example: Staging site
        // 'staging' => [
        //     'label' => 'Staging',
        //     'posts_endpoint' => 'https://staging.your-site.com/wp-json/wp/v2/posts',
        //     'pages_endpoint' => 'https://staging.your-site.com/wp-json/wp/v2/pages',
        //     'media_endpoint' => 'https://staging.your-site.com/wp-json/wp/v2/media',
        //     'wp_username'    => 'staging-user',
        //     'wp_app_password' => 'xxxx xxxx xxxx xxxx xxxx xxxx',
        //     'verify_ssl'     => false, // if using self-signed cert
        // ],

        // Example: Local development
        // 'local' => [
        //     'label' => 'Local Dev',
        //     'posts_endpoint' => 'http://localhost/wordpress/wp-json/wp/v2/posts',
        //     'pages_endpoint' => 'http://localhost/wordpress/wp-json/wp/v2/pages',
        //     'media_endpoint' => 'http://localhost/wordpress/wp-json/wp/v2/media',
        //     'wp_username'    => 'admin',
        //     'wp_app_password' => 'xxxx xxxx xxxx xxxx xxxx xxxx',
        //     'verify_ssl'     => false,
        // ],
    ],
];
