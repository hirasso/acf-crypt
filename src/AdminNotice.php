<?php

namespace Hirasso\ACFCrypt;

use Hirasso\ACFCrypt\Defuse\Crypto\Key;

/**
 * The main plugin class. Adds hooks and handles encryption/decryption
 */
final readonly class AdminNotice
{
    public function __construct(
        private string $type,
        private string $message
    ) {
        add_action('admin_notices', [$this, 'print_notice']);
    }

    /**
     * Prints a notice if there is no key defined
     */
    public function print_notice(): void
    {
        /** Only render the notice if editing ACF field groups */
        // if (!in_array(get_current_screen()?->post_type ?? null, ['acf-field-group'])) {
        //     return;
        // }

        $key = Key::createNewRandomKey();

        ob_start(); ?>
        <div class="notice notice-<?= $this->type ?>">
            <p>
                <?= $this->message ?>
            </p>
            <input onfocus="this.select()" type="text" class="acf-crypt-notice_suggestion" readonly value="define('ACF_CRYPT_KEY', '<?= $key->saveToAsciiSafeString() ?>');"></input>
            <p>
                <strong><span class="dashicons dashicons-warning"></span> Please Note:</strong>
                After you define and use the key to encrypt certain fields, make sure you never lose it. If you do, you won't be able to decrypt those fields again.
            </p>
        </div>
<?php echo ob_get_clean();
    }
}
