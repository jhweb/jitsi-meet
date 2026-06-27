# Sentinel Security Journal

## 2025-05-15 - Hardcoded Secrets and Mass Assignment Vulnerabilities
**Vulnerability:** Hardcoded dummy secrets in dead code and potential mass assignment on sensitive stream metadata.
**Learning:** Even "WIP" code can contain security risks like hardcoded secrets. Mass assignment vulnerabilities can lead to unauthorized modification of critical data like recording URLs.
**Prevention:** Always remove dead/WIP code before merging. Use framework-specific features (like Yii2 scenarios) to explicitly define safe attributes for mass assignment.
