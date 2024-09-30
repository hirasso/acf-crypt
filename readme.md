# acf-crypt

WIP – Protect sensitive data in your ACF fields using encryption 🔐

## Installation

### Via Composer (recommended):

1. Install the plugin:

```shell
composer require hirasso/acf-crypt
```

1. Activate the plugin manually or using WP CLI:

```shell
wp plugin activate acf-crypt
```

### Manually:

1. Download and extract the plugin
2. Copy the `acf-crypt` folder into your `wp-content/plugins` folder
3. Activate the plugin via the plugins admin page – Done!
4. Handle updates via [afragen/git-updater](https://github.com/afragen/git-updater)

## Setup

Upon activation, the plugin will display a unique encryption key for you to store in your `wp-config.php`:

![CleanShot 2024-09-30 at 13 14 34@2x](https://github.com/user-attachments/assets/28c38a0c-d95c-4d64-8365-85e20163c3fd)

> [!IMPORTANT]  
> If you loose this key, the values stored in encrypted fields won't be recoverable. Make sure to store it safely.

## Usage

Activate the option "Encrypt this field" for any text field in your field group settings:

![CleanShot 2024-09-30 at 13 09 46@2x](https://github.com/user-attachments/assets/35417313-6791-4880-8ef8-8b3969000b66)

The field's value will now be encrypted in your database:

![CleanShot 2024-09-30 at 13 11 19@2x](https://github.com/user-attachments/assets/64f81057-826f-4fab-8647-614f9c3e8a27)
