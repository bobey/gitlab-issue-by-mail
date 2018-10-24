# GITLAB ISSUE BY E-MAIL

Summary: create an Issue in Gitlab by sending an e-mail

## Why?

Gitlab is a fantastic solution to work with and manage source code and issues. Its main features are private repository hosting, issue tracking, code review, and complete workflow all the way through deployment and production.

As great as it is, there are still a few missing features that we'd all like to have. One of these is the ability to send an e-mail and have an issue automatically created.

## How does it work?

This simple CLI application checks an IMAP e-mail box for any new messages. It uses the subject of the e-mail as the issue title and the body of the e-mail as the issue description and creates a Gitlab issue using Gitlab's API, then the e-mail is deleted. This command can then be executed periodically with a crontab or systemd timer.

## DEPENDENCIES

- PHP >= `5.6.0`
- PHP extensions
  - `imap`
  - `pdo-pgsql`

## Quick Start

1. `git clone` this repository
2. `cp config.yaml.example config.yaml`
3. Edit `config.yaml` to match your setup
4. `composer install`
5. `./console gitlab:fetch-mail`

## `confg.yaml` Example

```yaml
mail:
    server:     imap.yourdomain.tld
    type:       imap
    port:       143
    username:   gouzigouza
    password:   passw0rd

gitlab:
    host:       gitlab.com
    projectId:  1
    token:      123456789
```

## Composer

This application uses `composer` to pull in 3rd-party dependencies. If you are unable to install composer from your operating system's repositories, you may use the provided script to fetch a local copy of it. To do this, run the following command:

`scripts/get_composer.sh` (run from the application's root directory)

This will create an executable file called `composer.phar`.

Executing `./composer.phar install` will install all dependencies

## Optional configuration

- Set up a crontab or systemd timer to periodically run `./console gitlab:fetch-mail` at regular intervals. This is outside the scope of this document.

## To-do / Planned Features

- Support STARTTLS
- Support multiple API keys (for different users)
- Extract attachments and add them to Gitlab issue
- Add unit tests
