# Changelog

All notable changes to project will be documented in this file.

## [unreleased]

### Bug Fixes

- phpstan update and fixes - ([4f320e8](https://github.com/roadiz/roadiz/commit/4f320e8fd39e547a3fb600ede9dc18ceb1f35b19)) - Ambroise Maupate

## [1.7.42](https://github.com/roadiz/roadiz/compare/v1.7.41...v1.7.42) - 2025-01-16

### Features

- Added NodeExplorerListEvent event to alter NodeExplorer list-manager filtering and sorting - ([65d030d](https://github.com/roadiz/roadiz/commit/65d030d8293f94be31f34d496b27a85be19ab58a)) - Ambroise Maupate

## [1.7.41](https://github.com/roadiz/roadiz/compare/v1.7.40...v1.7.41) - 2024-12-05

### Features

- Added default `stale-while-revalidate` cache control directive when public response - ([e2ad42f](https://github.com/roadiz/roadiz/commit/e2ad42f1f1d2317e764b471f76cb04cddc2024e1)) - Ambroise Maupate

## [1.7.40](https://github.com/roadiz/roadiz/compare/v1.7.39...v1.7.40) - 2024-10-11

### Bug Fixes

- Do not throw exception on bad page and itemPerPage argument, just use defaults fix roadiz/core-bundle-dev-app#20 - ([f0bb105](https://github.com/roadiz/roadiz/commit/f0bb1056c6c573851b547ffa127c83b3b1b9b708)) - Ambroise Maupate

## [1.7.39](https://github.com/roadiz/roadiz/compare/v1.7.38...v1.7.39) - 2024-07-10

### Bug Fixes

- Fixed Roadiz with no solr configured or no async messenger handling - ([acf92d4](https://github.com/roadiz/roadiz/commit/acf92d46c51b60b1b01d38a0dc69a3c4fb7e74bb)) - Ambroise Maupate

## [1.7.38](https://github.com/roadiz/roadiz/compare/v1.7.37...v1.7.38) - 2024-06-06

### Bug Fixes

- **(Solr)** Do not prevent saving content when Solr is not available. - ([00ed57e](https://github.com/roadiz/roadiz/commit/00ed57ef06b5b22e16f7abaf83de5e5233399074)) - Ambroise Maupate

## [1.7.37](https://github.com/roadiz/roadiz/compare/v1.7.36...v1.7.37) - 2023-11-11

### Bug Fixes

- Requires min doctrine/orm 2.8 because of `Query::toIterate` method - ([9227784](https://github.com/roadiz/roadiz/commit/9227784d45041c0ae89a567df225e1999dff1312)) - Ambroise Maupate

## [1.7.36](https://github.com/roadiz/roadiz/compare/v1.7.35...v1.7.36) - 2023-09-04

### Bug Fixes

- **(NodeNameChecker)** Limit generated unique nodeName to 250 chars, no matter suffix added to it - ([4dcf5d5](https://github.com/roadiz/roadiz/commit/4dcf5d57fa0757d959e29c9cde7082cefd4cf107)) - Ambroise Maupate
- **(doctrine/cache)** Fix doctrine/cache version to 1.12 due to ApcuCache signature change - ([9a9ef39](https://github.com/roadiz/roadiz/commit/9a9ef3916bd380b639a0d2e7ffd453ea909b4a53)) - Ambroise Maupate

## [1.7.35](https://github.com/roadiz/roadiz/compare/v1.7.34...v1.7.35) - 2023-08-01

### Bug Fixes

- **(RoutingExtensions)** Only append _preview query param for non-string routes - ([9b37f1b](https://github.com/roadiz/roadiz/commit/9b37f1b9401e6aad15efb102bfd68779e626411c)) - Ambroise Maupate

## [1.7.34](https://github.com/roadiz/roadiz/compare/v1.7.33...v1.7.34) - 2023-07-19

### Bug Fixes

- Fixed Argument #1 ($page) must be of type int, string given on `AbstractDoctrineExplorerProvider` - ([0f18ad5](https://github.com/roadiz/roadiz/commit/0f18ad55c3d741f192cca3ad024e9c7188a83ba8)) - Ambroise Maupate

## [1.7.33](https://github.com/roadiz/roadiz/compare/v1.7.32...v1.7.33) - 2023-07-06

### Bug Fixes

- Implemented __serialize() and __unserialize() in RZ\Roadiz\Core\Routing\NodePathInfo - ([6f09001](https://github.com/roadiz/roadiz/commit/6f090019e417fcff58419a81fc7e784af33348ec)) - Ambroise Maupate

## [1.7.32](https://github.com/roadiz/roadiz/compare/v1.7.31...v1.7.32) - 2023-07-06

### Bug Fixes

- Missing `setQueryCacheLifetime` call when Query `setCacheable(true)` is called - ([827afc2](https://github.com/roadiz/roadiz/commit/827afc29efaa6e094f519c77d12657ebbb290b1f)) - Ambroise Maupate

## [1.7.31](https://github.com/roadiz/roadiz/compare/v1.7.30...v1.7.31) - 2023-07-06

### Bug Fixes

- **(Search engine)** Do not add quotes if multi word exact query, Solr Helper already does it - ([6c6a727](https://github.com/roadiz/roadiz/commit/6c6a727d901bd76eb843f02662d1bc73ca7bab53)) - Ambroise Maupate

## [1.7.30](https://github.com/roadiz/roadiz/compare/v1.7.29...v1.7.30) - 2023-06-21

### Bug Fixes

- Nullable `getUrl` options array - ([c24f806](https://github.com/roadiz/roadiz/commit/c24f806b14a4efb40cf7d181330dcc2b84a70969)) - Ambroise Maupate

## [1.7.29](https://github.com/roadiz/roadiz/compare/v1.7.28...v1.7.29) - 2023-05-19

### Bug Fixes

- **(DebugBarSubscriber)** Test is Content-Type header exists before reading value - ([54e741c](https://github.com/roadiz/roadiz/commit/54e741c02a40c7262cfeb739e016abf0b0258431)) - Ambroise Maupate

## [1.7.28](https://github.com/roadiz/roadiz/compare/v1.7.27...v1.7.28) - 2023-04-12

### Bug Fixes

- Removed php80 only static return types - ([f940b20](https://github.com/roadiz/roadiz/commit/f940b202cba9897c0db4fcaa4df8a0b40bb923ea)) - Ambroise Maupate

## [1.7.27](https://github.com/roadiz/roadiz/compare/v1.7.26...v1.7.27) - 2023-04-11

### Bug Fixes

- Upgrade symfony/http-foundation to min 5.4.17 (IPv4-mapped IPv6 addresses incorrectly rejected) - ([c0424b6](https://github.com/roadiz/roadiz/commit/c0424b6592ff532acb1e74437c2bec6c4e542186)) - Ambroise Maupate

## [1.7.26](https://github.com/roadiz/roadiz/compare/v1.7.25...v1.7.26) - 2023-04-06

### Bug Fixes

- **(PreviewBarSubscriber)** Test if Response content is string before searching </body> tag - ([0392f46](https://github.com/roadiz/roadiz/commit/0392f46de76b6136c525d28bc4060a6d24d6dde8)) - Ambroise Maupate

## [1.7.25](https://github.com/roadiz/roadiz/compare/v1.7.24...v1.7.25) - 2023-03-16

### Bug Fixes

- **(Request)** Test request theme and _locale types to avoid errors when using POST body params - ([8005554](https://github.com/roadiz/roadiz/commit/800555428a45255a3d0380f96b63b60ffc48dfab)) - Ambroise Maupate

## [1.7.24](https://github.com/roadiz/roadiz/compare/v1.7.23...v1.7.24) - 2023-01-12

### Bug Fixes

- Fixed central_truncate twig filter - ([c79508e](https://github.com/roadiz/roadiz/commit/c79508e55db1646c68b69cf736a600c090d11f75)) - Ambroise Maupate

## [1.7.23](https://github.com/roadiz/roadiz/compare/v1.7.22...v1.7.23) - 2022-11-09

### Bug Fixes

- Missing allowRequestSearching condition on Paginator - ([686ce37](https://github.com/roadiz/roadiz/commit/686ce37eabab0fd9329813baebf2ab8837aaaaea)) - Ambroise Maupate

## [1.7.22](https://github.com/roadiz/roadiz/compare/v1.7.21...v1.7.22) - 2022-11-09

### Bug Fixes

- Make EntityListManager request sorting and searching optional for security purposes - ([e4ee554](https://github.com/roadiz/roadiz/commit/e4ee554776026ee98d8aceba210d59cc903632f4)) - Ambroise Maupate

## [1.7.21](https://github.com/roadiz/roadiz/compare/v1.7.20...v1.7.21) - 2022-09-15

### Bug Fixes

- Keep _preview query param during a Preview session - ([39efb77](https://github.com/roadiz/roadiz/commit/39efb77d854f6c54dc59c2f8df186381f113459a)) - Ambroise Maupate

## [1.7.20](https://github.com/roadiz/roadiz/compare/v1.7.19...v1.7.20) - 2022-07-21

### Bug Fixes

- Quick fix to prevent issue with solariumphp/solarium 6.2.5 and empty arrays - ([3916521](https://github.com/roadiz/roadiz/commit/39165217b2f1a1cd25a90ee5894db7c97a2e30d0)) - Ambroise Maupate

## [1.7.19](https://github.com/roadiz/roadiz/compare/v1.7.18...v1.7.19) - 2022-07-21

### Bug Fixes

- Missing NodeSourceXlsxSerializer service declaration - ([feb9134](https://github.com/roadiz/roadiz/commit/feb9134410ce1f048949e56d9dcd5f8b7edf82f8)) - Ambroise Maupate

## [1.7.18](https://github.com/roadiz/roadiz/compare/v1.7.17...v1.7.18) - 2022-07-01

### Bug Fixes

- Fixed symfony/form to 4.4.41 because of “multiple” option and array strict checking - ([23e63c5](https://github.com/roadiz/roadiz/commit/23e63c52358a55805fa642a412e9b45c8ee6d7d2)) - Ambroise Maupate

## [1.7.17](https://github.com/roadiz/roadiz/compare/v1.7.16...v1.7.17) - 2022-06-10

### Bug Fixes

- Strict typing on AttributeValueTranslation - ([5b5150b](https://github.com/roadiz/roadiz/commit/5b5150b9e336d35d7a51dc4cd9f1de9f40e85f02)) - Ambroise Maupate

## [1.7.16](https://github.com/roadiz/roadiz/compare/v1.7.15...v1.7.16) - 2022-05-25

### Bug Fixes

- Nullable CustomForm closeDate and email - ([b101df5](https://github.com/roadiz/roadiz/commit/b101df5f32cedc755c03d0e4ddb835039a835989)) - Ambroise Maupate

## [1.7.15](https://github.com/roadiz/roadiz/compare/v1.7.14...v1.7.15) - 2022-05-04

### Bug Fixes

- Added EmailManager From name from "email_sender_name" setting - ([ad43399](https://github.com/roadiz/roadiz/commit/ad43399b0e7499a798a95554ea0e1f2028be2039)) - Ambroise Maupate

## [1.7.14](https://github.com/roadiz/roadiz/compare/v1.7.13...v1.7.14) - 2022-04-26

### Bug Fixes

- Use Paginator to avoid pagination issue on indexing - ([f33c324](https://github.com/roadiz/roadiz/commit/f33c324a19a7c336b8a03c34dbf06e0bb3e97623)) - Ambroise Maupate

## [1.7.13](https://github.com/roadiz/roadiz/compare/v1.7.12...v1.7.13) - 2022-04-26

### Bug Fixes

- Fix Solr bulk reindexing logic and emptySolr query syntax - ([0537ad8](https://github.com/roadiz/roadiz/commit/0537ad851a2d6dd37d040791abde7174c4eece26)) - Ambroise Maupate

## [1.7.12](https://github.com/roadiz/roadiz/compare/v1.7.11...v1.7.12) - 2022-04-08

### Bug Fixes

- Common vulnerability fix where user can be redirected to external website - ([834c23c](https://github.com/roadiz/roadiz/commit/834c23c964eea7e42e8795c8ca42ada46b7f2c67)) - Ambroise Maupate

## [1.7.11](https://github.com/roadiz/roadiz/compare/v1.7.10...v1.7.11) - 2022-03-31

### Bug Fixes

- transactional email style btn - ([a3935e5](https://github.com/roadiz/roadiz/commit/a3935e52c6f25b74f4565a3ef097ab3ed0fbbc54)) - Ambroise Maupate

## [1.7.10](https://github.com/roadiz/roadiz/compare/v1.7.9...v1.7.10) - 2022-03-24

### Features

- added new UserJoinedGroupEvent and UserLeavedGroupEvent events - ([a6d77e8](https://github.com/roadiz/roadiz/commit/a6d77e8d37d8b4019d4c8ef113733fe0781241d7)) - Ambroise Maupate

## [0.1.0](https://github.com/roadiz/roadiz/compare/pre-0.1...v0.1.0) - 2014-12-23

### Bug Fixes

- this on static function. - ([5ae7db5](https://github.com/roadiz/roadiz/commit/5ae7db5231dd447b0af8e8a1f00d856570f42d3a)) - maxime constantinian

## [pre-0.1](https://github.com/roadiz/roadiz/compare/1.1.33...pre-0.1) - 2014-12-01

### Bug Fixes

- #120 and fix for the install theme form. - ([0659e88](https://github.com/roadiz/roadiz/commit/0659e88c63c2d26fbb5e1117a6011587b1ddb4ab)) - maxime constantinian

<!-- generated by git-cliff -->
