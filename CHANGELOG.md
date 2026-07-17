# Changelog

All notable changes to the AUREX project will be documented in this file.

Format based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

---

## [Unreleased]

### Added
- **GoRouter deep linking**: Replaced `Navigator.push` with 13 named routes. Centralized routing via `router.dart`. Support for deep linking and route parameter passing (`/analyze/:imageId`, `/result` via `extra`).
- **Keyboard accessibility**: `FocusTraversalGroup` + `FocusNode` chain in login/register screens. `ThemeData.focusColor` for visual focus indicators.
- **Bundle optimization**: Removed 6 unused dependencies (`camera`, `lottie`, `google_sign_in`, `firebase_core`, `firebase_auth`, `firebase_storage`).
- **Reduced motion support**: `SkeletonLoader` respects `MediaQuery.disableAnimations`. Shimmer animation skipped when system animations are off.
- **Dynamic font size**: `heading1Scaled(context)` and `bodyMediumScaled(context)` helpers with `textScaleFactor` clamping (0.8–1.3×).
- **Environment separation**: `AppEnvironment` enum (`development`, `staging`, `production`) with `AUREX_ENV` dart-define detection. `.env.staging` for Laravel backend.
- **API versioning**: All endpoints prefixed with `/api/v1/`. Flutter `environment.dart` and `api_service.dart` updated accordingly.
- **Database seeders**: `UserSeeder` with 3 realistic users (Demo, Test, Unverified).
- **PHPDoc**: Documented all controller methods (`AuthController`, `AnalysisController`, `HealthController`).
- **Password strength rules**: `RegisterRequest` now requires uppercase + number + symbol (`regex:/[A-Z]/`, `regex:/[0-9]/`, `regex:/[symbols]/`).
- **Load testing**: k6 script (`tests/k6/load-test.js`) with staged VU ramp-up, custom metrics, and performance thresholds.
- **Staging deployment**: `docker-compose.staging.yml`, Nginx reverse proxy config (`deploy/nginx/`), deployment script with rollback support (`deploy/staging/deploy.sh`), GitHub Actions auto-deploy workflow (`.github/workflows/deploy-staging.yml`).
- **Password strength unit tests**: 4 new tests in `FormRequestTest` (fails without uppercase, number, symbol; succeeds with strong password).

### Changed
- **Project structure**: Removed `flutter/` and `engine/` SDK directories from project root. Added comprehensive `.gitignore`.

### Fixed
- **AuthTest**: Fixed double-quoted URL strings not being updated during API versioning migration.
- **AuthTest**: Fixed `Hash::check` assertion using old weak password format.
- **AnalysisControllerTest**: Fixed double-quoted `/api/result/{id}` URLs missed during API versioning.
- **deploy.sh**: Fixed `-d` (directory check) → `-f` (file check) for docker-compose.staging.yml in backup logic.

---

## [1.0.0-alpha] — 2026-07-16

### Added
- Initial Flutter mobile app with Riverpod state management
- Laravel 12 backend API with Sanctum authentication
- Python FastAPI AI Service for face analysis
- Docker containerization (Laravel + MySQL + Redis + AI Service)
- 7 Form Request classes for centralized validation (Register, Login, UploadSelfie, Analyze, ForgotPassword, ResetPassword, ResendVerification)
- 5 API Resource classes for consistent response formatting (User, Analysis, AnalysisCollection, Recommendation, Image)
- FaceAnalysisService with retry mechanism and exponential backoff
- Monitoring: Health check API with DB/Redis/AI Service checks, Sentry error tracking, Pulse performance monitoring, Horizon queue monitoring
- Alerting rules (Slack/Email) for health check failures
- End-to-end tracing (X-Request-Id from Laravel → Flutter)
- Image optimization before upload (compress/resize via `flutter_image_compress`)
- Dark mode toggle with `DarkModeNotifier`
- Soft deletes for analyses, images, recommendations
- Semantic widgets (`Semantics`) in 7 screens + 3 shared widgets
- `flutter_secure_storage` for token storage
- Onboarding flow with 3 slides
- Empty states, error states, skeleton loading everywhere
- Logout confirmation dialog
- Logging middleware (Laravel `LogContextMiddleware`, Python structured logging)
- CI/CD pipeline (GitHub Actions with backend + Flutter + AI Service tests)
- Production Dockerfile (multi-stage build, OPcache, Composer optimizer)
- Comprehensive documentation (API_DOCS, TESTING_GUIDE, SETUP_GUIDE, CONTRIBUTING, README)
- 134 backend tests + 49 Flutter tests
