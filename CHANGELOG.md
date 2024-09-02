# Changelog

## [0.4.1](https://github.com/magewirephp/portman/compare/v0.4.0...v0.4.1) (2024-09-02)


### Bug Fixes

* **deps:** bump friendsofphp/php-cs-fixer from 3.63.2 to 3.64.0 ([546d176](https://github.com/magewirephp/portman/commit/546d176e65119dc7decb95a6770fc067ebd8fbaf))
* use correct additional config directory ([a915f00](https://github.com/magewirephp/portman/commit/a915f002a94774f594f84137f78df99f711fba55))

## [0.4.0](https://github.com/magewirephp/poortman/compare/v0.3.0...v0.4.0) (2024-08-30)


### Features

* rename Poortman to Portman ([8919a15](https://github.com/magewirephp/poortman/commit/8919a1538b2e83df0233fd497287dfa75a4f5b8c))


### Miscellaneous Chores

* update dependencies ([e3f6d31](https://github.com/magewirephp/poortman/commit/e3f6d310e8801b512fa9b6f8ee6a1b48a49b80e1))

## [0.3.0](https://github.com/magewirephp/portman/compare/v0.2.1...v0.3.0) (2024-08-30)


### Features

* add remove-properties config option ([0253a06](https://github.com/magewirephp/portman/commit/0253a0664b5666cc591c5cac50d1a9c80896b290))
* add transformations config for class-/namespace-renaming ([763b499](https://github.com/magewirephp/portman/commit/763b4999944cef1fef881340b1cb79659d4c7acc))
* class 'Renamer' is now a singleton for efficiency ([1ba9c3f](https://github.com/magewirephp/portman/commit/1ba9c3f4a59ddb8041ca8b8b81d091ed7ec3ad9c))
* directory & file filtering with glob & ignore ([4dd3e41](https://github.com/magewirephp/portman/commit/4dd3e41d4b945c21711456a867e76104b1c00687))
* file-doc-block transformation ([c3ef033](https://github.com/magewirephp/portman/commit/c3ef033a535acffa63ea75c722e6e99d476d2076))
* handle Trait and Class merges the same ([2f557da](https://github.com/magewirephp/portman/commit/2f557daf07c8f8758ca6c9526e60fd6ac336f661))
* implement 'TransformerConfiguration' singleton to centralize transformer config loading ([739cc62](https://github.com/magewirephp/portman/commit/739cc6248637bcdd8e2996f25f0482bc70e48336))
* refactor portman_config directories structure ([c2593a9](https://github.com/magewirephp/portman/commit/c2593a90fbd7ba0b36ae25c17490e5b28d20d3c1))
* refactor post-processors config ([44614ff](https://github.com/magewirephp/portman/commit/44614ff7d9c4ff86dd88a6cbb9fd9bf9463c086f))
* refactor TransformerConfiguration ([eceaed4](https://github.com/magewirephp/portman/commit/eceaed4eacb4832a6597ada07db1c858f6fb01b3))
* remove 'add-declare-strict' feature ([55cb5c4](https://github.com/magewirephp/portman/commit/55cb5c4698cd781815df5464c5ec60ab4464a37c))
* remove-methods transformation ([3803fc3](https://github.com/magewirephp/portman/commit/3803fc30d15df215711303f1cf306d6f0d89fc16))
* retrieve nested portman_config with 'dot' notation ([b6824b2](https://github.com/magewirephp/portman/commit/b6824b2c16217f8deee1a45a31a76f55502e62ed))


### Bug Fixes

* transformation has public props through magic-method ([e2fb304](https://github.com/magewirephp/portman/commit/e2fb304c9b579c36dd2cbac17b95311ea3db88d3))


### Miscellaneous Chores

* code formatting ([b696eb3](https://github.com/magewirephp/portman/commit/b696eb34e06a86a50b5d08a2453cd5cadbdda8a4))

## [0.2.1](https://github.com/magewirephp/portman/compare/v0.2.0...v0.2.1) (2024-08-29)


### Bug Fixes

* **deps:** bump friendsofphp/php-cs-fixer from 3.59.3 to 3.62.0 ([70a4836](https://github.com/magewirephp/portman/commit/70a48368df639e8acc9115aba6e8f6152a47e101))


### Miscellaneous Chores

* **deps-dev:** bump @commitlint/cli from 19.3.0 to 19.4.0 ([417b858](https://github.com/magewirephp/portman/commit/417b858b4ee33f978615e26a00233dcaf999ff2b))
* **deps-dev:** bump husky from 9.0.11 to 9.1.4 ([afc22ce](https://github.com/magewirephp/portman/commit/afc22ce3041217fd1c71450c73df55783a579e9c))
* **deps-dev:** bump husky from 9.1.4 to 9.1.5 ([811facc](https://github.com/magewirephp/portman/commit/811facc7951a71a7c7e4f08f49970067c28a4448))
* **deps-dev:** bump illuminate/log from 11.11.1 to 11.19.0 ([2b6c1f9](https://github.com/magewirephp/portman/commit/2b6c1f94c384dc07b2e155559bfb302acf466325))
* **deps-dev:** bump illuminate/log from 11.19.0 to 11.21.0 ([129623c](https://github.com/magewirephp/portman/commit/129623ccd802a87ade32a9e94e5431a5bcab15d9))
* **deps-dev:** bump larastan/larastan from 2.9.7 to 2.9.8 ([608da51](https://github.com/magewirephp/portman/commit/608da515459e6fccfecf72e455cf179412e70c7a))
* **deps-dev:** bump laravel/pint from 1.16.1 to 1.17.1 ([f9a585f](https://github.com/magewirephp/portman/commit/f9a585f3f106ab6faefc8704f7ee8384c50b5bc8))
* **deps-dev:** bump laravel/pint from 1.17.1 to 1.17.2 ([7e4c60d](https://github.com/magewirephp/portman/commit/7e4c60d399fb4df0061d86f5107558da7dcf7f1c))
* **deps-dev:** bump nikic/php-parser from 5.0.2 to 5.1.0 ([c2a486f](https://github.com/magewirephp/portman/commit/c2a486faaec0af8e55d22bf2c27be6c3033ba042))
* **deps-dev:** bump pestphp/pest from 2.34.8 to 2.35.0 ([48b3d50](https://github.com/magewirephp/portman/commit/48b3d50ab4d9d9ec6b09cf949fbdcdc85336466f))
* **deps-dev:** bump pestphp/pest from 2.35.0 to 2.35.1 ([b99b112](https://github.com/magewirephp/portman/commit/b99b112eeaf194a89a83753c710e94fac24ce698))
* **deps-dev:** bump rector/rector from 1.1.1 to 1.2.4 ([9e3d95a](https://github.com/magewirephp/portman/commit/9e3d95ab1930e9633c7d365e45b16632f2a1bc25))
* update dependencies ([6c11ab2](https://github.com/magewirephp/portman/commit/6c11ab2048ba1829ce38febf538c45e020bbf744))

## [0.2.0](https://github.com/magewirephp/portman/compare/v0.1.1...v0.2.0) (2024-06-23)


### Features

* add commitlint for correct commit-msgs ([8cdd942](https://github.com/magewirephp/portman/commit/8cdd942270b06ae4791623f79b6f67fc2fe73e9b))
* add dependabot settings ([7282700](https://github.com/magewirephp/portman/commit/7282700577312230d0061ad6f6d25b3a037be4d4))
* add Github actions Static Analysis ([28fa315](https://github.com/magewirephp/portman/commit/28fa3153fd452c7a125e8970dc8b599bd57ef365))
* add Github actions Static Analysis ([8cc5ebd](https://github.com/magewirephp/portman/commit/8cc5ebd2d385db9c132b5828c36336af70da5cdf))
* add PHPStan & fix errors ([bec8ad9](https://github.com/magewirephp/portman/commit/bec8ad924ee65f69b3c00f357f106950f06aa9e5))
* add Pint & fix errors ([ed1c3bd](https://github.com/magewirephp/portman/commit/ed1c3bd792fe2bb20c685ed1b35d1a46d9f3c13f))


### Bug Fixes

* add npm lock ([5d658a1](https://github.com/magewirephp/portman/commit/5d658a18086c517007459069710d29ab1109a756))
* **deps:** bump rector/rector from 1.1.0 to 1.1.1 ([18dddc1](https://github.com/magewirephp/portman/commit/18dddc1005fdb4238ba36197722f98ec0bfb6c82))
* expand the readme and ignore portman.log ([172c269](https://github.com/magewirephp/portman/commit/172c269a9c72444bef2128554b6c7d7da21731cd))

## [0.1.1](https://github.com/magewirephp/portman/compare/v0.1.0...v0.1.1) (2024-06-23)


### Bug Fixes

* add a basic readme ([8475c8e](https://github.com/magewirephp/portman/commit/8475c8e14b7233a0a78884da60f742de56f01cd8))
* give add-declare-strict option the name it deserves. ([898ba43](https://github.com/magewirephp/portman/commit/898ba437d6973f657ea75b2ccdeae5d9f8107d92))

## [0.1.0](https://github.com/magewirephp/portman/compare/v0.0.5...v0.1.0) (2024-06-23)

Initial release
