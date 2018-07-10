
# php-crypto library

A **Pure** PHP (without any external PHP-extension) Implementation of **BLAKE-256, GROESTL-256, JH-256, SKEIN-256 and KECCAK-256** hashing (cryptography) algorithms.
**shift-reality/php-crypto is licensed under the Apache License 2.0.**

**Installation (composer):**

0. Add git-repository:

`"repositories": [ { "url": "https://github.com/shift-reality/php-crypto.git", "type": "git", "reference": "newlib" } ]`

1. require library:

`"require": { "shift196/lib-akash": "1.0.*" }`

**Usage:**

0. Register default algo:

`\Shift196\AKashLib\Hasher::regBuiltinAlgos();`

1. Register own hash function(algo):

`\Shift196\AKashLib\Hasher::registerAlgo('MYHASH', new MyHashImpl());`

`MyHashImpl` should implement `\Shift196\AKashLib\IHashFunction` interface.

2. Make hash of hex-encoded data:

`$algo = 'BLAKE256 or GROESTL256 or JH256 or SKEIN256 or KECCAK256';`

`$hashHex = \Shift196\AKashLib\Hasher::doHash($algo, \Shift196\AKashLib\InputDataSupplier::forHex($dataHex))->hex(TRUE);`

3. Create own data supplier:

Just extend `\Shift196\AKashLib\InputDataSupplier` and implement abstract method `getInputData`.

!!!
!!! WARNING !!!
!!!
!!! Don't use internal classes from `\Shift196\AKashLib\Util` it can be changed without any notifications !!!
!!!

**TODO**:

-use PHPUnit for testing
-add more algos
-write Perf & Password class
