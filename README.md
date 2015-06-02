# Create a Gitlab issue from a mail

Add new issue to your Gitlab by sending an email to bug@yourdomain.com

## Why?

Gitlab is a fantastic tool to work with and is getting more awesome everyday.

It offers you in just a few minutes Private Repositories Hosting, great Code Review tooling for your team, Wiki,
Issue Tracker and everything.

But as awesome as it is, we must admit that the Issue Tracker lacks a few features. One of them, is the ability for your
team or customers to send a mail to some mail address of yours and see it transformed into a beautiful Gitab Issue.

This is the purpose of this ridiculously simple project.

## How does it work?

This project is a simple command you can execute every X minutes to poll mail from any address of yours.
If a new mail is detected, the script parse its content, create an issue with the mail subject as title and content as
description and delete it from your mail server. That's all. Nothing more!

## Setup

1. `git clone ...`
2. `cp config/parameters.yml.dist config/parameters.yml` and edit it to fit your needs

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

3. `bin/console gitlab:fetch-mail -v`

You should create some kind of CRON to run this command regularly.

## TODO

- Extract attachments and add them to Gitlab issue
- Add unit tests
