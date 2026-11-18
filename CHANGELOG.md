# Changelog

All notable changes to this project will be documented in this file.

## 1.1.0 - 2026-11-18

### Changed

- Requires the 2.0 of the suite core, which runs on [laravel-privacy](https://github.com/jeffersongoncalves/laravel-privacy), the engine shared by the privacy suites. No change to this package's API, config or tables.

## 1.0.0 - 2026-11-17

First release of laravel-us-privacy-opt-out, consumer choices under the US state privacy laws (on top of laravel-us-privacy).

- Opt-outs of sale, targeted advertising and profiling, and consent for sensitive data (opt-in states), with Utah/Iowa opt-out and Maryland's strictly-necessary rule.
- `allows()` / `allowsForRequest()` decide with the rules of the consumer's state; `@usPrivacyAllows('targeted_advertising')` Blade directive.
- `us-privacy.gpc` middleware: Global Privacy Control honored as an opt-out of sale and targeted advertising where the state requires universal opt-out mechanisms (`honor_gpc`: required, always or never).
- Append-only ledger with the consumer's state, optional endpoint for "Your Privacy Choices" links, and browser choices claimed on login.

## [Unreleased]
