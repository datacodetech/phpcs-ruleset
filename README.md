# Datacode PHP Code Standard
This repo contains the phpcs ruleset used by all of our other PHP projects.

The package should be included by composer
```
{
    "require": {
        "datacodetech/phpcs-ruleset": "^2.0.0"
    }
}
```
Then references in the `ruleset.xml` file for the project
```
<ruleset>
    <rule ref="vendor/datacodetech/phpcs-ruleset/DatacodeStandard" />
</ruleset>
```
