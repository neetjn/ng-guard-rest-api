# Guard REST

Guard was a licensing system I wrote late highschool, early college to help manage subscriptions for my online gaming community. This project is the source code for the REST api I developed between 2011 and 2013 (I was 15-17 at the time). This is vanilla PHP 5, I wrote the entire api from scratch. It also contains functionality for reading and operating against XenForo, as the Guard project was tightly coupled with my forum. It was also the first REST api I'd ever written, so there are some tacky/unintuitive conventions.

It was designed to be split into five core modules tiers admin, user, app, subscription, and utility. It was crafted to allow other members of the online community register their own projects/applications and create subscriptions using the Guard web portal found [here](https://github.com/neetjn/ng-guard-web-portal).

This project was running on a typical LAMP stack.