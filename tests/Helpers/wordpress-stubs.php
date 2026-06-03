<?php

/**
 * WordPress function stubs for testing.
 *
 * These stubs provide minimal implementations of WordPress functions
 * that MCP tools depend on. They are only loaded during testing.
 */

declare(strict_types=1);

if (! defined('ABSPATH')) {
    define('ABSPATH', '/tmp/wordpress/');
}

if (! function_exists('get_bloginfo')) {
    function get_bloginfo(string $show = ''): string
    {
        return match ($show) {
            'version' => '6.9.0',
            'name' => 'Test Blog',
            'url' => 'https://example.com',
            default => '',
        };
    }
}

if (! function_exists('get_stylesheet')) {
    function get_stylesheet(): string
    {
        return 'test-theme';
    }
}

if (! function_exists('get_template')) {
    function get_template(): string
    {
        return 'test-theme';
    }
}

if (! function_exists('get_site_url')) {
    function get_site_url(): string
    {
        return 'https://example.com';
    }
}

if (! function_exists('get_home_url')) {
    function get_home_url(): string
    {
        return 'https://example.com';
    }
}

if (! function_exists('is_multisite')) {
    function is_multisite(): bool
    {
        return false;
    }
}

if (! function_exists('get_locale')) {
    function get_locale(): string
    {
        return 'en_US';
    }
}

if (! function_exists('get_option')) {
    function get_option(string $option, mixed $default = false): mixed
    {
        static $options = [
            'blogname' => 'Test Blog',
            'siteurl' => 'https://example.com',
            'permalink_structure' => '/%postname%/',
            'active_plugins' => ['woocommerce/woocommerce.php'],
        ];

        return $options[$option] ?? $default;
    }
}

if (! function_exists('get_plugins')) {
    function get_plugins(): array
    {
        return [
            'woocommerce/woocommerce.php' => [
                'Name' => 'WooCommerce',
                'Version' => '9.0.0',
            ],
            'akismet/akismet.php' => [
                'Name' => 'Akismet Anti-spam',
                'Version' => '5.3',
            ],
        ];
    }
}

if (! function_exists('get_post_types')) {
    function get_post_types(array $args = [], string $output = 'names'): array
    {
        $types = [];

        if (! isset($args['_builtin']) || $args['_builtin'] !== false) {
            $post = new stdClass;
            $post->name = 'post';
            $post->label = 'Posts';
            $post->labels = (object) ['singular_name' => 'Post'];
            $post->public = true;
            $post->publicly_queryable = true;
            $post->show_in_rest = true;
            $post->has_archive = false;
            $post->hierarchical = false;
            $post->rewrite = ['slug' => 'posts'];
            $post->menu_icon = 'dashicons-admin-post';
            $post->capability_type = 'post';
            $types['post'] = $post;
        }

        if (! isset($args['_builtin']) || $args['_builtin'] !== true) {
            $book = new stdClass;
            $book->name = 'book';
            $book->label = 'Books';
            $book->labels = (object) ['singular_name' => 'Book'];
            $book->public = true;
            $book->publicly_queryable = true;
            $book->show_in_rest = true;
            $book->has_archive = true;
            $book->hierarchical = false;
            $book->rewrite = ['slug' => 'books'];
            $book->menu_icon = 'dashicons-book';
            $book->capability_type = 'post';
            $types['book'] = $book;

            $product = new stdClass;
            $product->name = 'product';
            $product->label = 'Products';
            $product->labels = (object) ['singular_name' => 'Product'];
            $product->public = true;
            $product->publicly_queryable = true;
            $product->show_in_rest = true;
            $product->has_archive = true;
            $product->hierarchical = false;
            $product->rewrite = ['slug' => 'products'];
            $product->menu_icon = 'dashicons-cart';
            $product->capability_type = 'post';
            $types['product'] = $product;
        }

        return $types;
    }
}

if (! function_exists('get_all_post_type_supports')) {
    function get_all_post_type_supports(string $postType): array
    {
        return match ($postType) {
            'book' => ['title' => true, 'editor' => true, 'thumbnail' => true],
            'product' => ['title' => true, 'editor' => true, 'thumbnail' => true, 'excerpt' => true],
            'post' => ['title' => true, 'editor' => true, 'comments' => true],
            default => ['title' => true],
        };
    }
}

if (! function_exists('get_object_taxonomies')) {
    function get_object_taxonomies(string $objectType): array
    {
        return match ($objectType) {
            'book' => ['genre'],
            'product' => ['product_cat', 'product_tag'],
            'post' => ['category', 'post_tag'],
            default => [],
        };
    }
}

if (! function_exists('get_taxonomies')) {
    function get_taxonomies(array $args = [], string $output = 'names'): array
    {
        $taxonomies = [];

        if (! isset($args['_builtin']) || $args['_builtin'] !== false) {
            $category = new stdClass;
            $category->name = 'category';
            $category->label = 'Categories';
            $category->labels = (object) ['singular_name' => 'Category'];
            $category->object_type = ['post'];
            $category->public = true;
            $category->hierarchical = true;
            $category->show_in_rest = true;
            $category->rewrite = ['slug' => 'category'];
            $taxonomies['category'] = $category;
        }

        if (! isset($args['_builtin']) || $args['_builtin'] !== true) {
            $genre = new stdClass;
            $genre->name = 'genre';
            $genre->label = 'Genres';
            $genre->labels = (object) ['singular_name' => 'Genre'];
            $genre->object_type = ['book'];
            $genre->public = true;
            $genre->hierarchical = true;
            $genre->show_in_rest = true;
            $genre->rewrite = ['slug' => 'genre'];
            $taxonomies['genre'] = $genre;
        }

        return $taxonomies;
    }
}
