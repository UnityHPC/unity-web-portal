# Overrides

In this location you can make folders which have the hostname of the portal.

For example, if the primary virtual host is `example.com`, but you wanted to host `example.org` as well, create an `example.org` folder in this location and structure it like the main deployment folder is structured. For now, you can override the following:

```
<override_hostname>
    config
        config.ini
```

Everything else in the deployment folder cannot be overridden.
