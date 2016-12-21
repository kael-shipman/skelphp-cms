# SkelPHP Cms

*NOTE: The Skel framework is an __experimental__ web applications framework that I've created as an exercise in various systems design concepts. While I do intend to use it regularly on personal projects, it was not necessarily intended to be a "production" framework, since I don't ever plan on providing extensive technical support (though I do plan on providing extensive documentation). It should be considered a thought experiment and it should be used at your own risk. Read more about its conceptual foundations at [my website](https://colors.kaelshipman.me/about/this-website).*

`Cms` is an extension of `Db`. It adds functionality for managing `Post`, `Page`, and `ContentTag` objects. Since these are standard DataClass objects, they can all be saved without much extra work. Note that each Class is responsible for knowing wether or not it is valid.

It's important to understand that this class is 100% arbitrary. I have designed the database to accommodate my needs for a Cms, but you could just as easily design a different database or a whole different Cms and not use this class at all. If you adhered to the Skel Cms interface, of course, then your class would be fully interoperable with any other implementations of the Skel framework, but this may not be of interest to you.

## Installation

Eventually, this package is intended to be loaded as a composer package. For now, though, because this is still in very active development, I currently use it via a git submodule:

```bash
cd ~/my-website
git submodule add git@github.com:kael-shipman/skelphp-cms.git app/dev-src/skelphp/cms
```

This allows me to develop it together with the website I'm building with it. For more on the (somewhat awkward and complex) concept of git submodules, see [this page](https://git-scm.com/book/en/v2/Git-Tools-Submodules).

