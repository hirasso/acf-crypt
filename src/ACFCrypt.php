<?php

namespace Hirasso\ACFCrypt;

use Hirasso\ACFCrypt\Defuse\Crypto\Crypto;
use Hirasso\ACFCrypt\Defuse\Crypto\Exception\WrongKeyOrModifiedCiphertextException;
use Hirasso\ACFCrypt\Defuse\Crypto\Key;

/**
 * The main plugin class. Adds hooks and handles encryption/decryption
 */
final class ACFCrypt
{
    /** the custom ACF field setting name */
    private static string $option_name = 'acfcrypt_encrypt';
    /** encrypted field values will be prefixed with this string: */
    private static string $encryption_prefix = 'acfcrypt_';
    private static Key $key;

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
     * Init. Return early if the global 'RHAU_ACF_CRYPT_KEY' is not defined
     */
    public static function init()
    {
        /** Key defined? */
        if (!defined('ACF_CRYPT_KEY')) {
            new AdminNotice(
                type: 'info',
                message: "<strong>ACF_CRYPT_KEY missing<strong>. Here's an example:"
            );
            return;
        }
        /** Key invalid? */
        if (empty(ACF_CRYPT_KEY) || strlen(ACF_CRYPT_KEY) < 30) {
            new AdminNotice(
                type: 'error',
                message: "<strong>Invalid ACF_CRYPT_KEY detected</strong>. Here's a valid example to be stored in your wp-config.php:"
            );
            return;
        }

        static::$key = Key::loadFromAsciiSafeString(ACF_CRYPT_KEY);

        add_action('acf/render_field_settings', [self::class, 'render_field_settings']);
        add_filter('acf/update_value', [self::class, 'update_value'], 11, 3);
        add_filter('acf/load_value', [self::class, 'load_value'], 11, 3);
    }

    /**
     * Check if a field supports encryption
     */
    private static function is_field_supported(array $field): bool
    {
        return in_array(
            needle: ($field['type'] ?? null),
            haystack: self::$supported_fields,
            strict: true
        );
    }

    /**
     * Check if a value is a non-empty string
     */
    private static function is_non_empty_string(mixed $value): bool
    {
        return is_string($value) && !empty(trim($value));
    }

    /**
     * Check if a field is set to be encrypted
     */
    public static function is_encrypted_field(array $field): bool
    {
        return !empty($field[static::$option_name]);
    }

    /**
     * Render a custom field setting to encrypt a field's value
     */
    public static function render_field_settings(array $field): void
    {
        if (!self::is_field_supported($field)) {
            return;
        }

        acf_render_field_setting($field, [
            'label'  => __('Encrypt this field'),
            'instructions' => 'Encrypt this fields\'s value in the database',
            'name' => self::$option_name,
            'type' => 'true_false',
            'ui' => 1,
        ]);
    }

    /**
     * Maybe encrypt a value upon saving it
     */
    public static function update_value(
        mixed $value,
        string|int $post_id,
        array $field
    ): mixed {
        if (!self::is_non_empty_string($value)) {
            return $value;
        }
        if (!self::is_encrypted_field($field)) {
            return $value;
        }
        return static::$encryption_prefix . Crypto::encrypt(stripslashes($value), static::$key);
    }

    /**
     * Maybe decrypt a value upon loading it
     */
    public static function load_value(
        mixed $value,
        string|int $post_id,
        array $field
    ): mixed {
        if (!self::is_non_empty_string($value)) {
            return $value;
        }
        if (!str_starts_with($value, static::$encryption_prefix)) {
            return $value;
        }
        $unprefixed = substr($value, strlen(static::$encryption_prefix));

        try {
            return Crypto::decrypt($unprefixed, static::$key);
        } catch (WrongKeyOrModifiedCiphertextException $exception) {
            // the field value was previously not yet encrypted
        }

        return $value;
    }


}
