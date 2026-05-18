# Usage

1. Enable module: `drush en spanish_municipalities_provider`.
2. Import migrations: `drush migrate:import municipalities --execute-dependencies`.
If you want only autonomies or provinces, run `drush migrate:import autonomies` or `drush migrate:import provinces --execute-dependencies`.
3. Uninstall module and it's dependencies:
```
drush pm:uninstall -y spanish_municipalities_provider migrate_source_csv migrate_tools migrate_plus migrate
```
Be aware, if you use some migration modules on your website, revise the list of uninstalled modules.
