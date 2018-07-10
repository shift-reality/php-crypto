
# php-crypto library

A Pure PHP (without any external PHP-extension) Implementation of BLAKE-256, GROESTL-256, JH-256, SKEIN-256 and KECCAK-256 hashing (cryptography) algorithms.

**Installation (composer):**

0. Add git-repository:

`"repositories": [ { "url": "https://github.com/shift-reality/php-crypto.git", "type": "git", "reference": "newlib" } ]`

1. require library:

`"require": { "shift196/lib-akash" }`

**Usage:**

0. Register default algo:

`Hasher::regBuiltinAlgos();`

1. Register own hash function(algo):

`Hasher::registerAlgo('MYHASH', new MyHashImpl());`

`MyHashImpl` should implement IHashFunction interface.

2. Make hash of hex-encoded data:

`$algo = 'BLAKE256 or GROESTL256 or JH256 or SKEIN256 or KECCAK256';`

`$hashHex = Hasher::doHash($algo, InputDataSupplier::forHex($dataHex))->hex(TRUE);`
