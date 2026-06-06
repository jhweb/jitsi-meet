## 2025-01-24 - [Authorization Bypass in RoomController]
**Vulnerability:** Several sensitive actions in `RoomController` (like `details`, `open`, `create`) were missing from `getAccessRules()`, potentially allowing unauthorized access to meeting data and recordings.
**Learning:** In Yii2/HumHub controllers, failing to explicitly list actions in `getAccessRules` (or similar access control filters) can lead to actions being public by default if not caught by global filters.
**Prevention:** Always verify that all public controller actions are explicitly covered by access rules.

## 2025-01-24 - [Hardcoded Secret in JoinRoomForm]
**Vulnerability:** A "WIP" method `getJwt` in `JoinRoomForm` contained a hardcoded secret.
**Learning:** Developers often leave placeholders or test code with secrets in "Work In Progress" sections.
**Prevention:** Regularly scan for high-entropy strings and secret patterns, especially in code marked as WIP or TODO.
