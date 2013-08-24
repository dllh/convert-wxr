convert-wxr
===========

A wp-cli script for converting old WXR files into versions that work with recent installs of WordPress.

Usage
-------

```wp convert-wxr --file=some-wxr.xml [--outfile=converted-wxr.xml]```

Other Info
----------

If you pass no --outfile, the converted file will be displayed to STDOUT.

Will not clobber an existing file.

There's a sane limit on the size of the input file.
