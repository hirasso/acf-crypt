<?php

namespace Hirasso\ACFEncryption;

/**
 * The main plugin class. Adds hooks and handles encryption/decryption
 */
class ACFEncryption
{
    private static string $option_name = '_acfcrypt_is_encrypted';
    private static string $algorithm = 'AES-256-CBC';
    private static string $passphrase;

    /**
     * These fields can be encrypted
     */
    private static array $supported_fields = [
        'text',
        'textarea',
        'wysiwyg',
        'email',
        'url',
        'password',
    ];

    /**
     * Init. Return early if the global 'RHAU_ACF_ENCRYPTION_KEY' is not set
     */
    public static function init()
    {
        if (!defined('ACF_CRYPTO_KEY') || empty(trim(ACF_CRYPTO_KEY))) {
            return;
        }

        self::$passphrase = hash('sha256', ACF_CRYPTO_KEY);

        add_action('acf/render_field_settings', [self::class, 'render_field_settings']);
        add_filter('acf/update_value', [self::class, 'update_value'], 1, 3);
        add_filter('acf/load_value', [self::class, 'load_value'], 1, 3);
    }

    /**
     * Check if a field supports encryption
     */
    private static function is_field_supported(array $field): bool
    {
        $type = $field['type'] ?? null;
        return in_array($field['type'], self::$supported_fields, true);
    }

    /**
     * Check if a field is set to be encrypted
     */
    private static function is_encrypted(
        mixed $value,
        array $field
    ): bool {
        if (!isset($field[static::$option_name]) || !$field[static::$option_name]) {
            return false;
        }
        return is_string($value) && !empty(trim($value));
    }

    /**
     * Render a custom field setting to encrypt a field's value
     */
    public static function render_field_settings(array $field): void
    {
        if (!self::is_field_supported($field)) {
            return;
        }

        acf_render_field_setting($field, array(
            'label'  => __('Encrypt field value'),
            'instructions' => 'Activate this if the field should store sensitive information',
            'name' => self::$option_name,
            'type' => 'true_false',
            'ui' => 1,
        ));
    }

    /**
     * Maybe encrypt a value upon saving it
     */
    public static function update_value(
        mixed $value,
        string|int $post_id,
        array $field
    ): mixed {
        if (self::is_encrypted($value, $field)) {
            return self::encrypt($value);
        }
        return $value;
    }

    /**
     * Maybe decrypt a value upon loading it
     */
    public static function load_value(
        mixed $value,
        string|int $post_id,
        array $field
    ): mixed {
        if (self::is_encrypted($value, $field)) {
            return self::decrypt($value);
        }
        return $value;
    }

    /**
     * Encrypt a string
     */
    private static function encrypt(string $decrypted): string
    {
        $iv = openssl_random_pseudo_bytes(16);
        $encrypted = openssl_encrypt(
            data: $decrypted,
            cipher_algo: static::$algorithm,
            passphrase: static::$passphrase,
            options: 0,
            iv: $iv
        );
        $encrypted = base64_encode($iv . $encrypted);
        dd($decrypted);
        return $encrypted;
    }

    /**
     * Decrypt a string
     */
    private static function decrypt(string $encrypted): string
    {
        $str = base64_decode($encrypted);
        $iv_len = openssl_cipher_iv_length(static::$algorithm);
        $iv = substr($str, 0, $iv_len);
        $value = substr($str, $iv_len);

        $decrypted = openssl_decrypt(
            data: $value,
            cipher_algo: static::$algorithm,
            passphrase: static::$passphrase,
            options: 0,
            iv: $iv
        );

        return $decrypted ?: $encrypted;
    }
}
