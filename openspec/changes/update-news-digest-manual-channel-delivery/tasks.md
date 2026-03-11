## 1. Implementation

- [ ] 1.1 Add admin endpoint `POST /admin/trigger/digest` in `news-maker-agent` settings router.
- [ ] 1.2 Add manual digest button in admin settings UI and wire it to the new endpoint.
- [ ] 1.3 Add single-flight lock and trigger helper for manual digest runs in scheduler/service layer.
- [ ] 1.4 Extend digest service with post-commit publish call to Core A2A (`/api/v1/a2a/send-message`, `tool=openclaw.send_message`).
- [ ] 1.5 Add secure config/env fields for Core invoke authentication (gateway token) and use them in outbound request headers.
- [ ] 1.6 Ensure delivery failure does not rollback digest persistence; log warning with trace/request correlation.

## 2. Tests

- [ ] 2.1 Unit test: manual trigger accepted when no digest run is in progress.
- [ ] 2.2 Unit test: manual trigger skipped when digest run lock is already held.
- [ ] 2.3 Integration/unit test: successful manual run creates digest and performs one Core A2A publish request.
- [ ] 2.4 Integration/unit test: no eligible items -> no publish request.
- [ ] 2.5 Integration/unit test: publish request failure -> digest still persisted, warning status logged.

## 3. Documentation

- [ ] 3.1 Update operator docs for manual digest publish flow (button -> channel delivery path).
- [ ] 3.2 Document required env vars and auth header behavior for `news-maker-agent` -> Core A2A delivery.
- [ ] 3.3 Update relevant `docs/agent-requirements/` guidance if outbound tool invocation contract is clarified.

## 4. Quality Checks

- [ ] 4.1 Run `openspec validate update-news-digest-manual-channel-delivery --strict`.
- [ ] 4.2 Run news-maker-agent test suite covering updated scheduler/admin/digest behavior.
