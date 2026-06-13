## 2025-05-15 - Missing Access Control on Sensitive Endpoints
**Vulnerability:** Several actions in `RoomController` (like `actionDetails`, `actionOpen`, `actionViewEvent`) were missing from `getAccessRules`, potentially allowing unauthorized access to meeting details, recordings, and chat logs.
**Learning:** Actions not explicitly listed in `getAccessRules` in HumHub controllers might be accessible by anyone if no default restrictive rule is applied.
**Prevention:** Always ensure all public-facing actions in a controller are explicitly covered by `getAccessRules` with appropriate permission checks.
