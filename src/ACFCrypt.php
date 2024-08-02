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
    private static string $option_name = 'acfcrypt_encrypt';
    private static Key $key;
    private static string $admin_notice_type = 'info';
    private static string $admin_notice_message = '';

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
        add_action('admin_notices', [self::class, 'print_key_suggestion_notice']);

        /** Key defined? */
        if (!defined('ACF_CRYPT_KEY')) {
            static::$admin_notice_type = 'info';
            static::$admin_notice_message = "<strong>ACF_CRYPT_KEY missing<strong>. Here's an example:";
            return;
        }
        /** Key invalid? */
        if (empty(ACF_CRYPT_KEY) || strlen(ACF_CRYPT_KEY) < 30) {
            static::$admin_notice_type = 'warning';
            static::$admin_notice_message = "<strong>Invalid ACF_CRYPT_KEY detected</strong>. Here's a valid example:";
            return;
        }

        static::$key = Key::loadFromAsciiSafeString(ACF_CRYPT_KEY);

        // dd(Crypto::decrypt(
        //     'def5020081875c60af40361684dc2a60d06d6c01318654de72c7a6cc327b4172c4480c1d81f02e1083d8bb8fafa23e63b6d05d4f04f0ac2f08d6a3834b426b224af0ae3dda178b9dc8e05228238343a9cb7f69a2fae665e22515bea2940392d0d6d4d4d992b05e81484a',
        //     static::$key
        // ));

        add_action('acf/render_field_settings', [self::class, 'render_field_settings']);
        add_filter('acf/update_value', [self::class, 'update_value'], 11, 3);
        add_filter('acf/load_value', [self::class, 'load_value'], 11, 3);
        add_filter('acf/prepare_field', [self::class, 'prepare_field']);
    }

    /**
     * Check if we have a valid key.
     * - is it defined?
     * - is it not empty?
     * - is it longer then 30 characters?
     */
    public static function is_key_defined()
    {
        return defined('ACF_CRYPT_KEY');
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

        acf_render_field_setting($field, [
            'label'  => __('Encrypt this field'),
            'instructions' => 'Encrypt this fields\'s value in the database',
            'name' => self::$option_name,
            'type' => 'true_false',
            'ui' => 1,
        ]);
    }

    /**
     * Prepare a field if it's encrypted. Render a shield after the field label
     */
    public static function prepare_field(?array $field): ?array
    {
        if (empty($field)) {
            return null;
        }
        if (!empty($field[static::$option_name])) {
            $field['label'] .= '<span class="dashicons dashicons-shield" style="font-size: 0.9em; width: 1em; height: 1.2em; display: inline-block; margin-left: 0.2em; vertical-align: middle;" title="encrypted"></span>';
        }
        return $field;
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
            // @TODO why does this need `stripslashes`?
            return Crypto::encrypt(stripslashes($value), static::$key);
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
            try {
                return Crypto::decrypt($value, static::$key);
            } catch (WrongKeyOrModifiedCiphertextException $exception) {
                // the field value was previously not yet encrypted
            }
        }
        return $value;
    }

    /**
     * Prints a notice if there is no key defined
     */
    public static function print_key_suggestion_notice(): void
    {
        /** Only render the notice if editing ACF field groups */
        if (!in_array(get_current_screen()?->post_type ?? null, ['acf-field-group'])) {
            return;
        }

        if (empty(static::$admin_notice_message)) {
            return;
        }

        $key = Key::createNewRandomKey();

        ob_start(); ?>
        <div class="notice notice-<?= static::$admin_notice_type ?>" id="acf-crypt-notice">
            <p>
                <?= static::$admin_notice_message ?>
            </p>
            <input onfocus="this.select()" type="text" id="acf-crypt-suggestion" readonly value="define('ACF_CRYPT_KEY', '<?= $key->saveToAsciiSafeString() ?>');"></input>
            <p>
                <strong><span class="dashicons dashicons-warning"></span> Please Note:</strong>
                After you define and use the key to encrypt certain fields, make sure you never lose it. If you do, you won't be able to decrypt those fields again.
            </p>
            <style>
                #acf-crypt-suggestion {
                    display: block;
                    width: 100%;
                    margin-block: 0.5rem;
                }
            </style>
        </div>
<?php echo ob_get_clean();
    }
}
