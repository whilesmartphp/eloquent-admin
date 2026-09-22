## [0.2.0] - 2026-09-22

### Added
- Offer providers, so a host's discounts can be listed and created from the console without the package knowing what stores them (`OfferProvider`, `OfferField`, `Offer`)
- `GET offers`, `POST offers/{provider}`, `DELETE offers/{provider}/{id}`, all answering to the configured owner the way the mail template endpoints do
- `admin.offer_providers` configuration, refused with a message when it names a class that does not implement the contract or two providers claiming one key
- `admin.requests.create_offer`, so a host can ask for its own fields when a discount is created

## [0.1.0] - 2026-08-29
- Shared administration user directory and configurable automatic email templates
- Host-configurable models, resources, requests, routes, middleware and user provider
- Generic admin reporting for every registered engagement metric provider
