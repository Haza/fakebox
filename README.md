Fakebox
=======

This little web application can be use to read email text files in a developpement environnement.

It is best use with [Nullmailer](http://untroubled.org/nullmailer/) and [Fakemail](http://www.lastcraft.com/fakemail.php).

## Nullmailer

Nullmailer is kind of sendmail wrapper which just relies messages to another mail host. Nullmailer provides sendmail binary and as the result all mail flows through it to the mail host you configure.

## Fakemail

A fake mail server that captures e-mails as files.

## How to use Fakebox

There is just one little line to change in index.php to match you configuration. The line is the ``$dir    = '/inbox';`` line. You need to change that to match the directory where you have told fakemail to store emails.

