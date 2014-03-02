This project is a chat module for [ATutor] (https://github.com/atutor/ATutor), an Open Source Web-based Learning Management System. The goal of this project was to make a new version of the chat based on XMPP protocol and WAI-ARIA live regions introducing more efficient data transfer, larger feature set, accessible and intuitive interface.
It was developed during [Google Summer of Code] (http://en.wikipedia.org/wiki/Google_Summer_of_Code) in 2012.

####Currently the XMPP Chat has the following features:
* One-to-one messaging and Multi User Chat (MUC) among course members;
* Roster management (tracking participants’ presence);
* Subscription management (automatically adding new joined users);
* Saving user’s nickname and password after first authorization;
* Saving history for both private and group chat messages;
* Highly secured authorization on third-party XMPP server;
* Offline messaging support even for group chats;
* Message encryption (on ATutor server);
* WAI-ARIA accessible regions;
* User friendly interface (“started typing” detection, ajax content loading, links highlighting, etc.).

####Requirements
The XMPP Chat uses the tools that provide all modern browsers: JavaScript and image support should be turned on. The PHP requirements are the same as for ATutor, 5.0.2+. This version is compatible with ATutor 2.1.1 and newer.

First you will need to have a working instance of ATutor, please get it from [github] (https://github.com/atutor/ATutor) or from [official website] (http://atutor.ca/atutor/). For a more detailed module-related information on installation, usage, screen readers compatibility, and troubleshooting please see the .pdf documents in the root folder.

####Tested environment
Currently the XMPP Chat was successfully tested on:
* Linux Mint 12, 14 (Ubuntu)
 * Browsers: Firefox 14.0.1, Chromium 18.0.1025.168, Opera 12.01;
 * Screen readers: Orca;
* Windows 7
 * Browsers: Firefox 12.0.1, Google Chrome 21.0.1180.75 m, Opera 12.01, Internet Explorer 9;
 * Screen readers: NVDA, JAWS.
