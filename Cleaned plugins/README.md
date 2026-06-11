## Cleaned PLugins

In this folder you will find plugins that are already on the wordpress marketplace but cleaned out.<br>
The plugins you see here are safe to use but not always up2date.<br>
I will try my best to keep them up2date. If you need a up2date plugin, just write me a <a href="https://github.com/ByAldon/WordPress/issues">issue</a>.<br>
Updates for these plugins are also turned off in wordpress for safety reasons.

### Clean install

When updating a plugin, it is advisable to first remove the entire plugin and any remnants thereof before installing the updated plugin to prevent problems.

## Plugin Cleanup Summary

The plugins were reviewed and cleaned with a focus on security, privacy, stability, and preventing unwanted automatic overwrites.

### General changes applied

* Checked for suspicious or malicious code patterns.
* Removed or disabled unnecessary promotional code, upsells, admin ads, and tracking-related features where possible.
* Disabled unnecessary telemetry, feedback, newsletter, and remote marketing requests.
* Improved input sanitization and output escaping in admin settings and plugin-generated content.
* Added or improved capability checks, nonce handling, and request validation where needed.
* Added direct access protection where appropriate.
* Removed or reduced unnecessary external resource loading, such as remote fonts or promotional images.
* Hardened uninstall and database-related operations where needed.
* Added update-lock protection to custom plugin builds to help prevent WordPress.org or automatic updates from overwriting the cleaned versions.
* Added custom version numbers to clearly identify the modified builds.
* Performed PHP syntax checks on the modified files.
* Tested the final ZIP packages for validity.

---

These modified builds are provided in the spirit of open source maintenance. They preserve the original licensing and attribution where applicable, clearly identify the changes made, and do not unlock paid features, bypass license checks, or misrepresent the original authors.
