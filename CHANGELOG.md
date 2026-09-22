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
