Use it if your sites have low level of data updates. Otherwise it is recommended to make caching **manually**!

Magic caching uses sqlite caching driver. Before enable it be shure to have pdo_sqlite php extension.

To make caching work just set `caching` in `Kohana::init` to `TRUE`.

It uses model names and column aliases as caching tags and generated SQLs as cache keys.

