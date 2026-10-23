## [0.3.0] - 2026-10-23

### Added
- `GET features`, answering to the configured owner, says which optional console pages this host serves: feedback once its package's routes are under the console prefix, PostHog once a shared dashboard is set
- `GET posthog`, answering to the configured owner, returns the publicly shared PostHog dashboard set in `admin.posthog.dashboard_url`. Only an https address of a shared dashboard is returned
- A `user` offer field, for an offer held for one person
- An offer provider may name the columns its offers fill with `columns()`
- `StripePromotionCodes` and `FreePasses` providers, for hosts with `whilesmart/entitlements-cashier` or `whilesmart/eloquent-entitlements`. Revoking a code also takes it off subscriptions already carrying it

### Fixed
- The user directory, a single user, and the engagement report answer to the configured owner, as every other endpoint here already did. They were reachable by any authenticated caller

## [0.2.0] - 2026-09-22

### Added
- Offer providers, so a host's discounts can be listed and created from the console without the package knowing what stores them (`OfferProvider`, `OfferField`, `Offer`)
- `GET offers`, `POST offers/{provider}`, `DELETE offers/{provider}/{id}`, all answering to the configured owner the way the mail template endpoints do
- `admin.offer_providers` configuration, refused when a class does not implement the contract or when two providers claim one key
- `admin.requests.create_offer`, so a host can validate a new discount its own way

## [0.1.0] - 2026-08-29
- Shared administration user directory and configurable automatic email templates
- Host-configurable models, resources, requests, routes, middleware and user provider
- Generic admin reporting for every registered engagement metric provider
