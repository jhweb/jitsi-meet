Changelog
=========

1.3.0 (July 27, 2026)
---------------------

### BREAKING

- **Webhook secret required in JaaS mode** — Webhook POSTs are rejected when `jaasWebhookSecret` is not configured. Previously, an empty secret allowed unsigned webhooks through (fail-open). Configure the secret in both 8x8 and HumHub before upgrading production.
- **Permission enforcement** — Controller actions now enforce permissions that were registered but previously unchecked:
  - `CreateVideoChat` — required to create rooms / streams
  - `JoinVideoChat` — required to open and join rooms
  - `ManageRecordings` — required to view stream details and recording artifacts
  Grant **Can create video chats** to member groups that should host meetings. See the README permission matrix.

### Security

- **Webhook fail-closed** — Empty webhook secret returns 401; signature oracle removed from logs; replay/drift tolerance enforced.
- **JWT server-side only** — JWTs no longer appear in URLs, browser history, or referrers; minted in `actionModal` only; moderator cache key normalized (`strtolower`).
- **Authorization / IDOR** — `actionDetails` requires creator, space membership, or `ManageRecordings`; container GUID validation on schedule and create prevents cross-space calendar injection.
- **SSRF allowlist** — Outbound fetches in stream details use HTTPS host allowlist; private/link-local IPs rejected; redirects disabled.
- **Mass assignment** — Recording and artifact URL fields removed from user-writable attributes; webhook paths assign explicitly.
- **Guest access** — Unauthenticated users redirected via `loginRequired()` on room open/modal paths.

### Other

- Raise minimum HumHub to **1.17** and PHP to **8.1** (matches HumHub 1.17 floor).
- Brand-neutral defaults; module identity `jitsi-meet-cloud-8x8` / `jhweb/jitsi-meet`.
- Standalone repo packaging: README, LICENSE, SECURITY.md, CONTRIBUTING.md.

1.2.1 (October 13, 2025)
------------------------
- Enh #45: Replace default meet.jit.si server domain (which has issues for the microphone and camera with the mobile app), with a list of popular ones

1.2.0 (August 27, 2025)
-----------------------
- Fix #40: Update module resources path
- Enh #41: Use PHP CS Fixer
- Enh #43: Migration to Bootstrap 5 for HumHub 1.18
1.1.9 (April 16, 2024)
----------------------
- Fix #38: Fix missing room prefix in JWT token

1.1.8 (January 30, 2024)
-------------------------
- Fix #37: Don't restrict an opening of a created room

1.1.7 (January 10, 2024)
-------------------------
- Fix #35: New group permission "Can access Jitsi Meet from main navigation"


1.1.6 (November 9, 2023)
-------------------------
- Fix #33: Fix visibility of the method `Controller::getAccessRules()`
- Fix #34: Fix JWT encoding function


1.1.5 (May 6, 2022)
-------------------
- Fix #30: Fix rooms loading


1.1.4 (April 18, 2022)
----------------------
- Fix #27: Fix assets on updating of disabled module


1.1.3 (April 14, 2022)
----------------------
- Enh: Disable Guest Access - Do not show menu item to users which are not logged in.


1.1.2 (September 12, 2020)
--------------------------
- Fix #10: Workaround for broken "open in app" link on Android devices
- Fix #11: Authentication failed with Jitsi Password


1.1.0 (April 05, 2020)
----------------------
- Enh #2: Add JWT authentication (thanks to @edmw)
- Fix #4: Added nonce to inline scripts


1.0.0 (March 22, 2020)
----------------------
Initial release
