<a name="unreleased"></a>
## [Unreleased]


<a name="v1.4.1"></a>
## [v1.4.1] - 2020-08-29
### Code Refactoring
- **DEFAULT_MAX_VALUE:** support 20 digits from 15 digits


<a name="v1.4.0"></a>
## [v1.4.0] - 2020-08-29
### Code Refactoring
- **_hashPassword:** replace hash cipher md5 to sha3-512
- **test:** replace aes 128 to 256


<a name="v1.3.0"></a>
## [v1.3.0] - 2020-08-29
### Features
- **ciphers:** add camellia-256-cbc support


<a name="v1.2.0"></a>
## [v1.2.0] - 2020-08-29
### Code Refactoring
- **ciphers:** remove all ecb ciphers support


<a name="v1.1.0"></a>
## [v1.1.0] - 2020-08-29
### Code Refactoring
- **Cryptomute:** remove const VERSION
- **DEFAULT_MAX_VALUE:** set to 999999999999999 from 9999999999
- **test:** replace expectedException
- **test:** replace openssl_random_pseudo_bytes to random_bytes
- **tests:** replace travis to github action
- **tests:** replace PHPUnit_Framework_TestCase namespace

### Features
- add aes-256-cbc cipher
- add type required


<a name="v1.0.3"></a>
## [v1.0.3] - 2016-07-15

<a name="v1.0.2"></a>
## [v1.0.2] - 2016-05-04
### Pull Requests
- Merge pull request [#14](https://github.com/teakowa/cryptomute/issues/14) from loostro/bugfix-value-range-less-than-input


<a name="v1.0.1"></a>
## [v1.0.1] - 2016-03-03
### Pull Requests
- Merge pull request [#12](https://github.com/teakowa/cryptomute/issues/12) from loostro/bugfix-error-in-comparison-11


<a name="v1.0.0"></a>
## v1.0.0 - 2016-02-17
### Pull Requests
- Merge pull request [#9](https://github.com/teakowa/cryptomute/issues/9) from loostro/feature-more-cipher-methods
- Merge pull request [#7](https://github.com/teakowa/cryptomute/issues/7) from loostro/feature-better-api-and-wider-test-coverage
- Merge pull request [#6](https://github.com/teakowa/cryptomute/issues/6) from loostro/feature-better-api-and-wider-test-coverage
- Merge pull request [#2](https://github.com/teakowa/cryptomute/issues/2) from loostro/feature-add-min-value
- Merge pull request [#1](https://github.com/teakowa/cryptomute/issues/1) from loostro/feature-add-key-and-password-setters


[Unreleased]: https://github.com/teakowa/cryptomute/compare/v1.4.1...HEAD
[v1.4.1]: https://github.com/teakowa/cryptomute/compare/v1.4.0...v1.4.1
[v1.4.0]: https://github.com/teakowa/cryptomute/compare/v1.3.0...v1.4.0
[v1.3.0]: https://github.com/teakowa/cryptomute/compare/v1.2.0...v1.3.0
[v1.2.0]: https://github.com/teakowa/cryptomute/compare/v1.1.0...v1.2.0
[v1.1.0]: https://github.com/teakowa/cryptomute/compare/v1.0.3...v1.1.0
[v1.0.3]: https://github.com/teakowa/cryptomute/compare/v1.0.2...v1.0.3
[v1.0.2]: https://github.com/teakowa/cryptomute/compare/v1.0.1...v1.0.2
[v1.0.1]: https://github.com/teakowa/cryptomute/compare/v1.0.0...v1.0.1
