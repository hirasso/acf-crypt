<?php

namespace Hirasso\ACFCrypt;

/**
 * Controls assets for enhancing encrypted fields
 */
final readonly class AdminUI
{
    public static function init()
    {
        add_action('acf/render_field', [self::class, 'render_field']);
        add_filter('acf/prepare_field', [self::class, 'prepare_field']);
        add_action('admin_enqueue_scripts', [self::class, 'enqueue_assets'], 100);
    }

    /**
     * Enqueues custom scripts and styles
     */
    public static function enqueue_assets(): void
    {
        wp_enqueue_style('acf-crypt-css', self::asset_uri('/assets-src/acf-crypt.css'), [], null);
        wp_enqueue_script('acf-crypt-js', self::asset_uri('/assets-src/acf-crypt.js'), ['jquery'], null, true);
    }

    /**
     * Helper function to get versioned asset urls
     */
    private static function asset_uri(string $path): string
    {
        $uri = ACF_CRYPT_PLUGIN_URI . '/' . ltrim($path, '/');
        $file = ACF_CRYPT_PLUGIN_DIR . '/' . ltrim($path, '/');

        if (file_exists($file)) {
            // deepcode ignore InsecureHash: not security related
            $uri .= "?v=" . hash_file('crc32', $file);
        }
        return $uri;
    }

    /**
     * Prepare a field if it's encrypted. Render a shield after the field label
     */
    public static function prepare_field(?array $field): ?array
    {
        if (empty($field)) {
            return null;
        }
        if (ACFCrypt::is_encrypted_field($field)) {
            $field['label'] .= '<span class="dashicons dashicons-shield" title="This field is encrypted"></span>';
            $field['wrapper']['class'] .= ' acfcrypt-field';
        }
        return $field;
    }

    /**
     * Render an encrypted field
     */
    public static function render_field(array $field): void
    {
        if (!ACFCrypt::is_encrypted_field($field)) {
            return;
        }
    }
}
