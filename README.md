# COUNTER SUSHI plugin

This plugin provides the NISO COUNTER-SUSHI Release 5 - RESTful API implementation for PKP software.

## Requirements



## Installation

Install this as a "generic" plugin in OJS.  The preferred installation method is via the Plugin Gallery.

To install manually via the filesystem, extract the contents of this archive to a "counterSushi" directory under "plugins/generic" in your OJS root.  To install via Git submodule, target that same directory path: `git submodule add https://github.com/shanu17/pkp-counterSushi plugins/generic/counterSushi` and `git submodule update --init --recursive plugins/generic/counterSushi`.  Run the installation script to register this plugin, e.g.: `php lib/pkp/tools/installPluginVersion.php plugins/generic/counterSushi/version.xml`.

## Usage

The URI *{base_url}*/api/*{version}*/stats/publications/sushi/reports/ will respond to the GetReport requests. For example:
* Fetch the COUNTER Journal Requests (Excluding OA_Gold) [TR_J1] between 2020 and 2021:
  * /ojs/index.php/myJournal/api/v1/stats/publications/sushi/reports/tr_j1?customer_id=anonymous&begin_date=2020&end_date=2021

Your base URL and journal name will vary.

## Author / License

Written by Sudheendra Kusume for the [University of Pittsburgh](http://www.pitt.edu).  Copyright (c) University of Pittsburgh.

Released under a license of GPL v2 or later.