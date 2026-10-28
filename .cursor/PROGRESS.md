# Progress

Last updated: 2026-07-27T16:00:00Z
Current stage: children 1–10 all have open PRs
Do not touch jitsi-meet-cloud.

## Upstream

- `jitsi-meet-improved` @ `80a62e4`

## PRs → jitsi-meet-improved (merge order)

| Seq | PR | Child | Security review |
|-----|-----|-------|-----------------|
| 1 | [#9](https://github.com/jhweb/jitsi-meet/pull/9) | Docker | n/a |
| 2 | [#10](https://github.com/jhweb/jitsi-meet/pull/10) | Brand | n/a |
| 3 | [#11](https://github.com/jhweb/jitsi-meet/pull/11) | Webhook | PASS conditional (validate fixed in #15) |
| 4 | [#12](https://github.com/jhweb/jitsi-meet/pull/12) | JWT | PASS conditional + console redact `84af0cb` |
| 5 | [#13](https://github.com/jhweb/jitsi-meet/pull/13) | Authz | PASS (remote tip verified; earlier FAIL was stale checkout) |
| 6 | [#14](https://github.com/jhweb/jitsi-meet/pull/14) | SSRF | PASS conditional (medium: DNS rebinding follow-up) |
| 7 | [#15](https://github.com/jhweb/jitsi-meet/pull/15) | Settings GUI | includes ConfigController validate() |
| 8 | [#17](https://github.com/jhweb/jitsi-meet/pull/17) | Room UI a11y | stacks #12 |
| 9 | [#16](https://github.com/jhweb/jitsi-meet/pull/16) | Packaging | n/a |
| 10 | [#18](https://github.com/jhweb/jitsi-meet/pull/18) | QA checklist | local Docker recorded; staging human-only |

## Notes

- Staging deploy / real 8x8 / release tag: **human operator only**
- Pre-deploy: set `jaasWebhookSecret` before #11
- SSRF medium DNS-rebinding: recommended follow-up, not blocking
