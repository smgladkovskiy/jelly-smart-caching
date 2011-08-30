Use it if your sites have low level of data updates. Otherwise it is recommended to make caching **manually**!

Magic caching uses sqlite caching driver. Before enable it be shure to have pdo_sqlite php extension.

To make caching work just set `caching` in `Kohana::init` to `TRUE` and place this module right after defining jelly istelf.

It uses model names and column aliases as caching tags and generated SQLs as cache keys.

If you want to extend `Jelly_Builder` class, make the new one extending the `Caching_Jelly_Builder` class. Or just make `My_Jelly_Builder` extends `Jelly_Builder`.