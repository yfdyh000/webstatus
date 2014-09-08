Web Status
=========

Python script and PHP Web views used to analyze Web projects based on Gettext (.po) files.
* ```script/webstatus.py``` generates a JSON file for all listed projects.
* ```index.php``` is used to display the content of the JSON file, per project or per locale.
* ```mpstats/index.php``` is used to display projects related to Marketplace for all locales in a single page.

Prerequisites:
* Copy ```config/config.ini-dist``` as ```config/config.ini```, adapting the path to your system. This is the path used to store all local clones (currently about 1 GB of space required).
* You need ```git```, ```svn``` and ```msgfmt```(included in package *gettext*) on your system.

## Available URLS
```
/
```
Main Web Status view.

```
/mpstats
```
Marketplace Stats view.

See a running instance at http://l10n.mozilla-community.org/~flod/webstatus/

## Structure of the Json file

```JSON
locale_code: {
    product_id: {
        "complete": true/false,
        "error_message": "",
        "error_status": true/false,
        "fuzzy": number of fuzzy strings,
        "name": pretty name to display,
        "percentage": percentage of translated strings,
        "total": total number of strings,
        "translated": number of translated strings,
        "untranslated": number of untranslated strings
    }
}
```
