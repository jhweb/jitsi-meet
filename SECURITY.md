# Security Policy

## Supported versions

| Version | Supported |
|---------|-----------|
| 1.3.x   | Yes       |
| 1.2.x   | No        |
| < 1.2   | No        |

Security fixes are released on the `jitsi-meet-improved` branch and tagged for Marketplace distribution.

## Reporting a vulnerability

**Do not open public GitHub issues for security vulnerabilities.**

Report privately to the repository maintainer:

- **Email:** [12529192+jhweb@users.noreply.github.com](mailto:12529192+jhweb@users.noreply.github.com)
- **GitHub:** Use [Private vulnerability reporting](https://github.com/jhweb/jitsi-meet/security/advisories/new) if enabled on the repository

Include:

- Affected version and HumHub/PHP versions
- Steps to reproduce
- Impact assessment (confidentiality, integrity, availability)
- Suggested fix if available

We aim to acknowledge reports within 5 business days and will coordinate disclosure timing with the reporter.

## Scope

In scope: this HumHub module (`jitsi-meet-cloud-8x8`), its PHP controllers, models, webhook handler, JWT generation, and outbound fetch logic.

Out of scope: HumHub core, 8x8 JaaS infrastructure, Jitsi Meet client, and third-party server configuration outside this module.
