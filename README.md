<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

<p align="center">
<a href="https://github.com/laravel/framework/actions"><img src="https://github.com/laravel/framework/workflows/tests/badge.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/dt/laravel/framework" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/v/laravel/framework" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/l/laravel/framework" alt="License"></a>
</p>

## About Laravel

Laravel is a web application framework with expressive, elegant syntax. We believe development must be an enjoyable and creative experience to be truly fulfilling. Laravel takes the pain out of development by easing common tasks used in many web projects, such as:

- [Simple, fast routing engine](https://laravel.com/docs/routing).
- [Powerful dependency injection container](https://laravel.com/docs/container).
- Multiple back-ends for [session](https://laravel.com/docs/session) and [cache](https://laravel.com/docs/cache) storage.
- Expressive, intuitive [database ORM](https://laravel.com/docs/eloquent).
- Database agnostic [schema migrations](https://laravel.com/docs/migrations).
- [Robust background job processing](https://laravel.com/docs/queues).
- [Real-time event broadcasting](https://laravel.com/docs/broadcasting).

Laravel is accessible, powerful, and provides tools required for large, robust applications.

## Learning Laravel

Laravel has the most extensive and thorough [documentation](https://laravel.com/docs) and video tutorial library of all modern web application frameworks, making it a breeze to get started with the framework. You can also check out [Laravel Learn](https://laravel.com/learn), where you will be guided through building a modern Laravel application.

If you don't feel like reading, [Laracasts](https://laracasts.com) can help. Laracasts contains thousands of video tutorials on a range of topics including Laravel, modern PHP, unit testing, and JavaScript. Boost your skills by digging into our comprehensive video library.

## Laravel Sponsors

We would like to extend our thanks to the following sponsors for funding Laravel development. If you are interested in becoming a sponsor, please visit the [Laravel Partners program](https://partners.laravel.com).

### Premium Partners

- **[Vehikl](https://vehikl.com)**
- **[Tighten Co.](https://tighten.co)**
- **[Kirschbaum Development Group](https://kirschbaumdevelopment.com)**
- **[64 Robots](https://64robots.com)**
- **[Curotec](https://www.curotec.com/services/technologies/laravel)**
- **[DevSquad](https://devsquad.com/hire-laravel-developers)**
- **[Redberry](https://redberry.international/laravel-development)**
- **[Active Logic](https://activelogic.com)**

## Contributing

Thank you for considering contributing to the Laravel framework! The contribution guide can be found in the [Laravel documentation](https://laravel.com/docs/contributions).

## Code of Conduct

In order to ensure that the Laravel community is welcoming to all, please review and abide by the [Code of Conduct](https://laravel.com/docs/contributions#code-of-conduct).

## Security Vulnerabilities

If you discover a security vulnerability within Laravel, please send an e-mail to Taylor Otwell via [taylor@laravel.com](mailto:taylor@laravel.com). All security vulnerabilities will be promptly addressed.

## Loan product management

Administrators can manage an organisation's lending catalogue at
`/admin/loan-products`. The catalogue supports searching, status filters,
pagination, product details, creation, editing, activation/deactivation, and
deletion of products not referenced by lending records. Product data is
restricted to the signed-in administrator's organisation.

Loan amounts and fixed fees are entered in KES and stored in cents; percentage
rates are stored as decimal fractions. The `interest_period` field records
whether a rate is for the full loan or per month. Existing products default to
`per_loan` to preserve their current pricing meaning. Monthly-rate and
reducing-balance products cannot be activated until the loan pricing engine
supports those calculations.

Run `php artisan db:seed --class=LoanProductSeeder` to add six proposed,
inactive product drafts for the `lendflow` organisation. These are
illustrative configurations, not verified lender quotations. The seeder does
not overwrite existing products. It uses `BG-PROP` and `AF-PROP` because `BG`
and `AF` are already used by the existing catalogue.

## M-Pesa B2C disbursements

Set `MPESA_ENV=production` to use the live Safaricom API (`https://api.safaricom.co.ke`).
Use `MPESA_ENV=sandbox` only for testing. Configure the separate B2C Daraja credentials in `.env` (`MPESA_B2C_CONSUMER_KEY`,
`MPESA_B2C_CONSUMER_SECRET`, `MPESA_B2C_SHORTCODE`, `MPESA_INITIATOR_NAME`, and
`MPESA_SECURITY_CREDENTIAL`). Set `MPESA_B2C_RESULT_URL` and
`MPESA_B2C_TIMEOUT_URL` to publicly reachable HTTPS URLs for the organisation's
B2C callback routes, or set `APP_URL` to the public HTTPS application URL.
Disbursement is blocked when callbacks resolve to a local/non-HTTPS address.

Run a Laravel queue worker that consumes the default queue; disbursement and
M-Pesa callback jobs use that queue. Loans remain pending until Daraja confirms
the transfer through the result callback.

## Daraja API operations

Admins can submit Daraja operations from **Payments → Daraja API Operations**.
The console supports Reversal, C2B v1/v2 URL registration, Transaction Status,
Account Balance, B2C, STK Push/query, the three listed B2B requests, Dynamic QR,
and Bill Manager opt-in, invoicing, reconciliation, cancellation, and update
requests. OAuth tokens are fetched and cached automatically. `MPESA_ENV`
selects the production or sandbox host. Asynchronous response callbacks are
stored against a private callback token and shown in the operation history.

Set `APP_URL` to the public HTTPS application URL before submitting production
requests that require callbacks. C2B registration uses the app's existing
organisation callback routes. Other asynchronous operations get callback URLs
generated for each request.

Configure each enabled Daraja app's credentials in `.env`:

- `MPESA_CONSUMER_KEY` / `MPESA_CONSUMER_SECRET` for the existing C2B/STK app.
- `MPESA_B2C_CONSUMER_KEY` / `MPESA_B2C_CONSUMER_SECRET` and the B2C
  initiator/security credential for B2C.
- `MPESA_REVERSAL_CONSUMER_KEY`, `MPESA_REVERSAL_CONSUMER_SECRET`,
  `MPESA_REVERSAL_INITIATOR_NAME`, `MPESA_REVERSAL_SECURITY_CREDENTIAL`, and
  `MPESA_REVERSAL_RECEIVER_PARTY` for Reversal.
- `MPESA_OPERATIONS_CONSUMER_KEY` / `MPESA_OPERATIONS_CONSUMER_SECRET` and
  `MPESA_OPERATIONS_INITIATOR_NAME` / `MPESA_OPERATIONS_SECURITY_CREDENTIAL`
  for Transaction Status and Account Balance.
- `MPESA_B2B_CONSUMER_KEY` / `MPESA_B2B_CONSUMER_SECRET`,
  `MPESA_B2B_INITIATOR_NAME`, and `MPESA_B2B_SECURITY_CREDENTIAL` for B2B.
- `MPESA_BILLMANAGER_CONSUMER_KEY` / `MPESA_BILLMANAGER_CONSUMER_SECRET` for
  Bill Manager.

Do not reuse credentials between Daraja apps unless Safaricom has enabled the
relevant products on that app. Confirm the production `ReceiverParty` and
Reversal permissions with Safaricom before submitting a live reversal. A
Reversal initiated from a payment record leaves the payment posted until a
successful Reversal result callback arrives. Requests submitted from the
operations console without a linked payment record are tracked as provider
operations and do not change the lending ledger.

## Branches and loan approvals

Run `php artisan migrate` to install the branch-scoped approval workflow. Existing
organisations receive a Main Branch; unassigned loan officers, supervisors,
borrowers, and applications are assigned to it. Previously approved applications
without a resulting loan are returned to the review queue so they pass through
both approval levels. Review the migration and existing application queue before
deploying it to a live database.

Assign each loan officer and supervisor to an active branch from **Admin →
Users**. Manage branches from **Admin → Branches**. New organisations created
after the migration receive a Main Branch and a Supervisor role automatically.

Loan officers register borrowers and originate applications. New borrower KYC
starts as pending and must be verified by a different, authorized supervisor or
administrator. Supervisors review collateral evidence, record independent
verification, and provide first-level decisions. A different administrator must
give final approval before an application can be disbursed. The application,
borrower, and private security-file views are restricted by organisation and
branch/ownership; approval and verification actions are audited.

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
