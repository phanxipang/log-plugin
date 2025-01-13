# Fansipan PSR-7 Request Matcher

[![Latest Version on Packagist][ico-version]][link-packagist]
[![Github Actions][ico-gh-actions]][link-gh-actions]
[![Codecov][ico-codecov]][link-codecov]
[![Total Downloads][ico-downloads]][link-downloads]
[![Software License][ico-license]](LICENSE.md)

PSR-7 request matcher equivalent of [Symfony's RequestMatcher](https://github.com/symfony/symfony/blob/master/src/Symfony/Component/HttpFoundation/RequestMatcherInterface.php).

## Installation

You may use Composer to install this package:

``` bash
composer require fansipan/request-matcher
```

## Usage

To create a matcher instance with your assertions, you can use the following example to match the request host:

```php
use Fansipan\RequestMatcher\HostRequestMatcher;
use Psr\Http\Message\RequestInterface;

$matcher = new HostRequestMatcher('localhost');

// Matches http://localhost

/** @var RequestInterface $request */
$matcher->matches($request);
```

### Customer Request Matcher

You can also create a matcher using a callback. For instance:

```php
use Fansipan\RequestMatcher\CallbackRequestMatcher;
use Psr\Http\Message\RequestInterface;

$matcher = new CallbackRequestMatcher(static fn (RequestInterface $request) => $request->getUri()->getScheme() === 'https' && $request->getUri()->getHost() === 'my.app');
```

### Chain Request Matcher

The example above can be grouped by using `ChainRequestMatcher`

```php
use Fansipan\RequestMatcher\CallbackRequestMatcher;
use Fansipan\RequestMatcher\HostRequestMatcher;
use Fansipan\RequestMatcher\SchemeRequestMatcher;
use Psr\Http\Message\RequestInterface;

$matcher = new ChainRequestMatcher([
    new SchemeRequestMatcher('https'),
    new HostRequestMatcher('my.app'),
]);
```

## Testing

```bash
composer test
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) and [CODE_OF_CONDUCT](CODE_OF_CONDUCT.md) for details.

## Security

If you discover any security related issues, please email contact@lynh.me instead of using the issue tracker.

## Credits

- [Lynh](https://github.com/jenky)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.

[ico-version]: https://img.shields.io/packagist/v/fansipan/request-matcher.svg?style=for-the-badge
[ico-license]: https://img.shields.io/badge/license-MIT-brightgreen.svg?style=for-the-badge
[ico-gh-actions]: https://img.shields.io/github/actions/workflow/status/phanxipang/request-matcher/testing.yml?branch=main&label=actions&logo=github&style=for-the-badge
[ico-codecov]: https://img.shields.io/codecov/c/github/phanxipang/request-matcher?logo=codecov&style=for-the-badge
[ico-downloads]: https://img.shields.io/packagist/dt/fansipan/request-matcher.svg?style=for-the-badge

[link-packagist]: https://packagist.org/packages/fansipan/request-matcher
[link-gh-actions]: https://github.com/phanxipang/request-matcher
[link-codecov]: https://codecov.io/gh/phanxipang/request-matcher
[link-downloads]: https://packagist.org/packages/fansipan/request-matcher
