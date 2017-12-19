#  Changelog

## v0.4.2 (2017-12-19)

* fix: force default protocol if not defined for a route ([36c9486](https://github.com/RETFU/RREST/commit/36c9486))

## v0.4.1 (2017-12-19)

* fix: protocol can't be override for a specific route ([5309218](https://github.com/RETFU/RREST/commit/5309218))

## v0.4.0 (2017-11-23)

* refactor: add Util\HTTP::getHeader ([bcfb052](https://github.com/RETFU/RREST/commit/bcfb052))
* refactor: add Util\HTTP::getProtocol ([775c725](https://github.com/RETFU/RREST/commit/775c725))
* refactor: extract Protocol validation ([888d5db](https://github.com/RETFU/RREST/commit/888d5db))
* refactor: extract Content-Type header validation ([cefe94b](https://github.com/RETFU/RREST/commit/cefe94b))
* refactor: extract Accept header validation ([fc95c46](https://github.com/RETFU/RREST/commit/fc95c46))
* chore: add PHP 7.2 support for Travis CI ([360d770](https://github.com/RETFU/RREST/commit/360d770))
* refactor: extract controller/method validation/getter from RREST ([9c1e8c0](https://github.com/RETFU/RREST/commit/9c1e8c0))

## v0.3.0 (2017-11-16)

* test: add more tests for the JsonGuard error conversion ([9dc2667](https://github.com/RETFU/RREST/commit/9dc2667))
* refactor: rework JsonGuard message conversion ([502f36d](https://github.com/RETFU/RREST/commit/502f36d))
* chore: add atoum html code coverage report ([71675bd](https://github.com/RETFU/RREST/commit/71675bd))
* feat: better error message when I/O validation fail ([745df3e](https://github.com/RETFU/RREST/commit/745df3e))
* chore: update jsonguard 1.0 ([b69a3cd](https://github.com/RETFU/RREST/commit/b69a3cd))
