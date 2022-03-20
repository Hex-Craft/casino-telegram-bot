# Telegram Casino Bot
## Description

Telegram bot with slots & balance check.

- /menu - opens menu;
- /ping - pong;
- /freespin - free spin;
- /connect {username} {password} - connects account with site;

To use next command, user account needs to be connected with site.

- /disconnect - disconnects user telegram from site;
- /check - checks if user connected with site;
- /balance - shows site balance;
- /spin - slot spin with bet 100;
- /spin {bet} - spin with custom bet;

## Installation

Need to have site with users table, which must contain ``telegram_id`` and ``user_balance`` rows.  
  
Then run:  
``docker-compose up``

Done.