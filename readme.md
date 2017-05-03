## Anchor CMS

[![Codacy Badge](https://api.codacy.com/project/badge/Grade/6c810b451dc6481da8bbdee5bf7dcd44)](https://www.codacy.com/app/craigchilds94/anchor-cms?utm_source=github.com&amp;utm_medium=referral&amp;utm_content=anchorcms/anchor-cms&amp;utm_campaign=Badge_Grade)
[![SensioLabsInsight](https://insight.sensiolabs.com/projects/6d47b69c-c54b-4875-8d88-4cec20ff676c/mini.png)](https://insight.sensiolabs.com/projects/6d47b69c-c54b-4875-8d88-4cec20ff676c) [![Latest Stable Version](https://poser.pugx.org/anchorcms/anchor-cms/v/stable)](https://packagist.org/packages/anchorcms/anchor-cms)

Anchor is a super-simple, lightweight blog system, made to let you just write. [Check out the site](http://anchorcms.com/).

### Requirements

- PHP 7
- A Database (Sqlite/MySQL/PostgreSQL)

### Install

    composer create-project anchorcms/anchor-cms anchor

### Updating

    composer update

### Testing

    php composer.phar update --dev
    php vendor/bin/phpspec run
