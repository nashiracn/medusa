Medusa is a command line tool that works together with Satis to create a local
git mirror for your composer projects.

**What the hell???**

# What is Medusa, what is it good for?

If you have a very slow connection, fetching your project's dependencies through
[composer](http://getcomposer.org) can be a pain. My projects were taking more
than half a day to update or install on my local machines because of slow networks.

Medusa will create a mirror of all these things on your local machine and let you
fetch everything from there rather than fetching the whole source from Github. Each
dependency is entirely mirrored, meaning you'll have all versions, tags, and branches
on your local machine.

# Limitations

It will only work with github hosted projects for now.

It has very poor documentation.

It is a very early release, there might be bugs, and the API to use it is
definitely confusing.

# How to use

For now, you can do the following:

* Download the .phar archive from the downloads section
* Download the .phar archive for SATIS
* Put them both in a folder on your machine
* Inside of that folder, create a `web/` and a `web/repositories/` folder
* Create a `medusa.json` file that looks like this:

```
    {
        // vcs repositories not in packagist
        "repositories": [
            {
                // pseudo package name; used for repo directory structure
                "name": "myvendor/package",
                "url": "git@othervcs:myvendor/package.git"
            }
        ]
        "require": [
            "vendor/package",
            "othervendor/otherpackage",
            //... List all the packages you want here, there dependencies can be
            // auto downloaded as well
        ],
        "repodir": "web/repositories",
        // Optional URL to satis (if not hosted locally)
        "satisurl": "http://user:password@satis.host:port/repositories",
        // Target path for generated satis configuration
        "satisconfig": "satis.json"
    }
```
* Create a satis config file skeleton like this:

```
    {
        "name": "My Repository",
        "homepage": "http://packages.example.org",
        "repositories": [
            // Optionally list repositories not updateable by medusa
        ],
        "require-all": true // if you want to also mirror the dependencies from each package
    }
```
* run `./medusa.phar mirror medusa.json`
* wait a long time

During this time, medusa will first find all the dependencies you need. Then it
runs `git clone --mirror` for each of them to create a mirror inside of the
specified repodir. Finally, it updates your satis.json file with your new config.

* Run the satis build command: `./satis.phar build satis.json web/`
* Once a day run:

```
    ./medusa.phar update medusa.json
    ./satis.phar build satis.json web/
```
To update all repos and rebuild the satis config.

# Other available commands:

`add [--with-deps] package [config-file]`

* `--with-deps` if you want to also mirror the new package's dependencies
* `package` is the package name you want to mirror (eg: symfony/symfony)
* `config-file` is the medusa.json config file; the specified satis.json config file will be updated

# Use a Socks5 Proxy:

`ALL_PROXY=socks5h://127.0.0.1:1987 ./bin/medusa mirror medusa.json`

# Make composer use it

Point a webserver to the `web/` directory.

In your composer global config file add:

```
    {
        "repositories": [
            {
                "type": "composer",
                "url": "http://my.satis.url"
            }
        ]
    }
```
