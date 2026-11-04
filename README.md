<p align="center">
  <a href="https://www.buckaroo.nl">
    <img src="https://raw.githubusercontent.com/buckaroo-it/Media/main/Buckaroo/README.md%20Headers/buckaroo-magento1-header-rounded.png" alt="Buckaroo — Payments for Magento 1" width="100%">
  </a>
</p>

<h1 align="center">Buckaroo for Magento 1</h1>

<p align="center">
  A payment gateway for Magento 1 webshops.<br>
  No longer actively developed — see the notice below before you install it.
</p>

<p align="center">
  <a href="#about">About</a> ·
  <a href="#requirements">Requirements</a> ·
  <a href="#installation">Installation</a> ·
  <a href="#upgrade">Upgrade</a> ·
  <a href="#configuration">Configuration</a> ·
  <a href="#support">Support</a> ·
  <a href="#contribute">Contribute</a>
</p>

---

> [!WARNING]
> **This plugin is no longer actively developed.** Magento 1 reached end of life in June 2020 and Adobe no longer publishes updates or security patches for it. We still ship occasional service and security patches for merchants who are not yet able to migrate, but no new payment methods or features are added.
>
> If you are starting a new webshop, or planning a migration, use the [Buckaroo Magento 2 plugin](https://github.com/buckaroo-it/Magento2) instead.

---

## About

Magento Open Source, previously Magento Community Edition, is an open source e-commerce platform. Adobe only publishes updates for its successor, Magento 2.

This plugin connects a Magento 1 webshop to the Buckaroo payment gateway. Buckaroo is a Dutch Payment Service Provider. More than 54,000 organisations use the Buckaroo platform to process their payments, subscriptions and unpaid invoices.

[Full documentation on docs.buckaroo.io](https://docs.buckaroo.io)

---

## Requirements

| Requirement | Supported versions |
|---|---|
| Magento | 1.9.x |
| PHP | 5.6 or higher, as far as your Magento or OpenMage version supports |
| Composer | 1.x or 2.x |

You also need a Buckaroo account. Don't have one yet? [Request an account](https://www.buckaroo.nl/start).

> [!NOTE]
> The plugin also runs on [OpenMage](https://www.openmage.org/), the community-maintained fork of Magento 1. OpenMage supports newer PHP versions, so it is worth considering if a migration to Magento 2 is not yet realistic.

---

## Installation

We recommend installing the plugin with Composer. It is the easiest way to install, update and maintain.

Sign in over SSH and run the following from your Magento root folder:

```bash
composer require buckaroo/magento1
```

Composer installs the files in `vendor/buckaroo/magento1`. Copy them into your installation:

```bash
cp -ra vendor/buckaroo/magento1/app/* YOUR_MAGENTO_INSTALLATION_ROOT/app
cp -ra vendor/buckaroo/magento1/skin/* YOUR_MAGENTO_INSTALLATION_ROOT/skin
```

Then flush the cache and sessions:

```bash
rm -rf MAGENTO_INSTALLATION_ROOT/var/cache/*
rm -rf MAGENTO_INSTALLATION_ROOT/var/session/*
```

> [!IMPORTANT]
> Check the file access rights afterwards. Composer runs as the logged-in user, which is often not the web server user, so the copied files can end up unreadable by the web server. That causes behaviour that is hard to trace back to permissions.

---

## Upgrade

```bash
composer update buckaroo/magento1
```

Then repeat the copy and cache steps from the installation above.

> [!TIP]
> Always test an upgrade on a staging environment first and check the [release notes](https://github.com/buckaroo-it/Magento/releases) for breaking changes.

---

## Configuration

Configure the plugin in your Magento admin, under **System → Configuration → Sales → Payment Methods**.

You will need your **Store key** and **Secret key**, which you can find under [API credentials in Buckaroo Plaza](https://plaza.buckaroo.nl/Configuration/Merchant/ApiKeys).

Step-by-step instructions are in the [documentation](https://docs.buckaroo.io) and in `Installatiehandleiding.txt` in this repository.

---

## Support

Having trouble? Work through this list before reaching out:

1. Check the [knowledge base and FAQ](https://docs.buckaroo.io).
2. Confirm you are on the [latest release](https://github.com/buckaroo-it/Magento/releases).
3. Confirm the files were copied into `app` and `skin`, and that the web server can read them.
4. Verify that your push URL is reachable from outside your network. Buckaroo sends push messages from fixed IP addresses and ports, so make sure these are on your allow list. See [push messages](https://docs.buckaroo.io/docs/integration-push-messages) for the current list.

Still stuck? Contact us and include your Magento or OpenMage version, plugin version, PHP version, the relevant log lines and the transaction key.

- **Bug reports:** [open an issue](https://github.com/buckaroo-it/Magento/issues)
- **Technical support:** [support@buckaroo.nl](mailto:support@buckaroo.nl)
- **Phone:** +31 (0)30 711 50 50
- **Gateway status:** [status.buckaroo.io](https://status.buckaroo.io/)

Because the plugin is no longer actively developed, we cannot take on feature requests for it.

---

## Contribute

Please read our [Contribution Guidelines](https://github.com/buckaroo-it/Magento/blob/master/CONTRIBUTING.md) before opening a pull request, and target the `master` branch. Given the status of this plugin, we only accept fixes, not new functionality.

Found a security issue? Please report it privately as described in our [security policy](https://github.com/buckaroo-it/Magento/blob/master/SECURITY.md), rather than opening a public issue.

---

## Versioning

We follow semantic versioning (`MAJOR.MINOR.PATCH`):

- **MAJOR** — breaking changes that require additional testing and caution.
- **MINOR** — new functionality with limited impact.
- **PATCH** — bug fixes and hotfixes only.

All changes are documented in `RELEASE_NOTES.txt` and on the [releases page](https://github.com/buckaroo-it/Magento/releases).

---

<p align="center">
  <sub>Made with care by <a href="https://www.buckaroo.nl">Buckaroo</a>.<br>
  This document is subject to change; typos and language errors are possible.</sub>
</p>
