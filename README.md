# Jitsi Meet Cloud (8x8 JaaS) for HumHub

HumHub module for video conferencing with [8x8 JaaS](https://jaas.8x8.vc/) cloud integration or self-hosted Jitsi servers. Schedule meetings, manage live streams, recordings, and transcripts from within your HumHub community.

Based on [jhweb/jitsi-meet](https://github.com/jhweb/jitsi-meet) (AGPL-3.0). Extended with 8x8 JaaS, live-stream management, calendar scheduling, and security hardening.

## Compatibility

| Requirement | Version |
|-------------|---------|
| HumHub | **1.17+** (Bootstrap 3; tested on 1.17.x) |
| PHP | **8.1+** (HumHub 1.17 minimum) |
| Optional | [Calendar module](https://github.com/humhub/calendar) for scheduling |

> **Note:** This module targets HumHub 1.17 / Bootstrap 3. A future HumHub 1.18 / Bootstrap 5 branch may follow upstream's `bs5` branch — not included here.

## Screenshots

| | |
|---|---|
| ![Live streams overview](resources/screen1.PNG) | ![Room creation](resources/screen2.PNG) |
| ![Meeting in progress](resources/screen3.PNG) | ![Stream details](resources/screen4.PNG) |

## Installation

### HumHub Marketplace

1. Open **Administration → Modules → Marketplace**.
2. Search for **Jitsi Meet Cloud (8x8 JaaS)**.
3. Install and enable the module.
4. Open **Administration → Modules → Jitsi Meet Cloud (8x8 JaaS) → Configure**.

### Manual install

1. Clone or download this repository.
2. Copy the module directory into your HumHub `protected/modules/` folder as `jitsi-meet-cloud-8x8`.
3. Run `composer install --no-dev` inside the module directory (requires PHP 8.1+ and Composer).
4. Enable the module under **Administration → Modules**.
5. Configure connection settings (see below).

See also [docs/INSTALLATION.md](docs/INSTALLATION.md) for CSP requirements.

## Configuration

### 8x8 JaaS mode

1. Set **Mode** to **8x8 JaaS** in module settings.
2. Enter your **App ID**, **Key ID (KID)**, and **Private key path** from the [8x8 Developer Console](https://developer.8x8.com/jaas/docs/webhook-setup).
3. Place the private key on the server (e.g. `/var/www/keys/jaas_private.pem`) with restrictive permissions:

   ```bash
   chmod 600 /var/www/keys/jaas_private.pem
   chown www-data:www-data /var/www/keys/jaas_private.pem
   ```

4. Configure the webhook URL shown in module settings in your 8x8 app (e.g. `https://your-humhub.example/jitsi-meet-cloud-8x8/webhook`).
5. Set a **Webhook secret** in both 8x8 and HumHub module settings.

> **Breaking change (v1.3.0):** Webhooks are **rejected** when no webhook secret is configured. You must set `jaasWebhookSecret` in JaaS mode before webhooks will be accepted. See [Upgrade notes](#upgrade-notes).

### Self-hosted Jitsi

1. Set **Mode** to **Self-hosted**.
2. Enter your Jitsi server domain (e.g. `meet.example.org`).
3. Optionally enable JWT authentication and configure the shared secret.
4. A list of tested public servers is available in the configuration UI if you do not run your own server.

## Permission matrix

Configure under **Administration → Users → Permissions** (global) or per-space **Space settings → Permissions**.

| Permission | Purpose | Default (members) |
|------------|---------|-------------------|
| **Can access Jitsi Meet** (`CanAccess`) | View the live-streams menu and index | Allowed |
| **Can create video chats** (`CreateVideoChat`) | Start new rooms / create streams | Denied — grant to groups that should host |
| **Can join video chats** (`JoinVideoChat`) | Open and join existing rooms | Allowed |
| **Can be moderator** (`CanBeModerator`) | Receive moderator JWT features in JaaS | Admins / moderators |
| **Enable recording** (`EnableRecording`) | JWT feature flag for recording | Restricted |
| **Enable livestreaming** (`EnableLivestreaming`) | JWT feature flag for livestream | Restricted |
| **Can manage recordings** (`ManageRecordings`) | View stream details, download recordings | Denied — admins only |
| **Can schedule** (`CanSchedule`) | Create calendar-linked meetings | Requires Calendar module |

> **Breaking change (v1.3.0):** `CreateVideoChat`, `JoinVideoChat`, and `ManageRecordings` are now enforced on controller actions. Users who could previously join without explicit grants may need permission updates. Grant **Can create video chats** to member groups that should start rooms.

## Calendar dependency

Scheduling features require the HumHub **Calendar** module to be installed and enabled. Without it, scheduling permissions and UI are hidden.

## Local development

A Docker-based HumHub 1.17 environment is provided on the `chore/dev-docker-env` branch:

```bash
docker compose -f docker/docker-compose.dev.yml up -d
```

HumHub will be available at [http://localhost:9091](http://localhost:9091). The module source is bind-mounted for live editing.

See [CONTRIBUTING.md](CONTRIBUTING.md) for the full development workflow.

## Testing and QA

Manual verification steps are documented in [docs/QA/manual-verification.md](docs/QA/manual-verification.md) (maintained by the QA staging plan).

## Troubleshooting

### Content Security Policy (CSP)

If you override HumHub's default CSP, allow your Jitsi server in `script-src` and `frame-src`. Full guidance: [docs/INSTALLATION.md](docs/INSTALLATION.md#csp-security-hardening).

### Webhooks not updating streams

- Confirm **Webhook secret** is set in both 8x8 and HumHub (required since v1.3.0).
- Check **Administration → Information → Logging** for signature or drift errors.
- Verify the webhook URL is reachable from the internet.

### Users cannot create or join rooms

After upgrading to v1.3.0, review group permissions for **Can create video chats** and **Can join video chats** (see [Permission matrix](#permission-matrix)).

## Upgrade notes

### 1.3.0 (security hardening)

**BREAKING:**

1. **Webhook secret required** — All webhook POSTs are rejected when `jaasWebhookSecret` is empty in JaaS mode. Configure the secret before upgrading production.
2. **Permission enforcement** — Create, join, and recording-management actions now require `CreateVideoChat`, `JoinVideoChat`, and `ManageRecordings` respectively. Audit group permissions after upgrade.

See [docs/CHANGELOG.md](docs/CHANGELOG.md) for the full list of security fixes.

## License

[GNU Affero General Public License v3.0](LICENSE) (AGPL-3.0).

Upstream attribution: [jhweb/jitsi-meet](https://github.com/jhweb/jitsi-meet) — HumHub community module originally derived from the Jitsi Meet HumHub module.

## Security

Report vulnerabilities privately — see [SECURITY.md](SECURITY.md).
