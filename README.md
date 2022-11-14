<p align="center">
  <img src="https://www.buckaroo.nl/media/3472/magento1_icon.png" width="200px" position="center">
</p>

# Buckaroo Magento Payments Plugin
[![Latest release](https://badgen.net/github/release/buckaroo-it/Magento)](https://github.com/buckaroo-it/Magento/releases)

### Index
- [About](#about)
- [Requirements](#requirements)
- [Installation](#installation)
- [Upgrade](#upgrade)
- [Configuration](#configuration)
- [Contribute](#contribute)
- [Versioning](#versioning)
- [Additional information](#additional-information)
---

### About

Magento Open Source, previously Magento Community Edition, is an open-source eCommerce platform. Please note that Magento is currently end of life and Adobe only publishes updates for Magento 2 which is the successor (newer version).

The Buckaroo Payments Plugin ([Dutch](https://support.buckaroo.nl/categorieen/plugins/magento-1) or [English](https://support.buckaroo.eu/categories/plugins/magento-1)) for Magento enables a ready-to-sell payment gateway. You can choose from popular online payment methods in The Netherlands, Belgium, France, Germany and globally.
Start accepting payments within a few minutes.

### Requirements

To use the Buckaroo plugin, please be aware of the following minimum requirements:
- A Buckaroo account ([Dutch](https://www.buckaroo.nl/start) or [English](https://www.buckaroo.eu/solutions/request-form))
- Magento version 1.9.x
- PHP 7.4 or higher

### Installation

We recommend you to install the Buckaroo Magento Payments plugin with composer. It is easy to install, update and maintain.

**Login with SSH and execute the following commands via the command line:**
```
composer require buckaroo/magento1
```

**Extension activation and updates**

Execute the following commands via the command line:
```
cp -ra vendor/buckaroo/magento1/app/* YOUR_MAGENTO_INSTALLATION_ROOT/app
cp -ra vendor/buckaroo/magento1/skin/* YOUR_MAGENTO_INSTALLATION_ROOT/skin
```

**File access rights**

Depending on the operating system, make sure the just added files have the correct access rights so that the web server can access and execute the files correctly.

The composer command can be executed by the logged in user on the server which is sometimes not the same as the user of the web server. The files created by composer can not be read by the web server which can lead to unexpected behavior.
> Composer will install the files in:
vendor/buckaroo/magento1


**Flush cache and sessions**

Flush your Magento cache using the following command:
```
rm -rf MAGENTO_INSTALLATION_ROOT/var/cache/*
rm -rf MAGENTO_INSTALLATION_ROOT/var/session/*
```
**The installation is now completed.**

### Upgrade
To be able to make use of the latest features and bugfixes you should update the extension frequently. To do this you have to execute the following commands and flush your Magento cache.
```
composer update buckaroo/magento1
```

### Configuration

For the configuration of the plugin, please refer to our [Dutch](https://support.buckaroo.nl/categorieen/plugins/magento-1) or [English](https://support.buckaroo.eu/categories/plugins/magento-1) support website. You'll find all the needed information there.
You can also contact our [technical support department](mailto:support@buckaroo.nl) if you still have some unanswered questions.

### Contribute

We really appreciate it when developers contribute to improve the Buckaroo plugins.
If you want to contribute as well, then please follow our [Contribution Guidelines](CONTRIBUTING.md).

### Versioning 
<p align="left">
  <img src="https://www.buckaroo.nl/media/3480/magento_versioning.png" width="500px" position="center">
</p>

- **MAJOR:** Breaking changes that require additional testing/caution.
- **MINOR:** Changes that should not have a big impact.
- **PATCHES:** Bug and hotfixes only.

### Additional information
- **Knowledge base & FAQ:** Available in [Dutch](https://support.buckaroo.eu/categories/plugins/magento-1) or [English](https://support.buckaroo.nl/categorieen/plugins/magento-1).
- **Support:** https://support.buckaroo.eu/contact
- **Contact:** [support@buckaroo.nl](mailto:support@buckaroo.nl) or [+31 (0)30 711 50 50](tel:+310307115050)

