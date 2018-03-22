# CSV_Sploder - The Big CSV Benchmark!
Split a CSV file of n columns into files <n>.csv with columns[0,n] using different languages to see which works fastest

Here are the languages and versions + required modules.
The files and invocations are set up with a SHEBANG line like so:
`#!/usr/bin/env <Interpreter>`
and should be directly executable.

## Perl (v5.18.2)
Requires module:
Text::CSV_XS (Compiled CSV Library)

Install:
`$ cpan Text::CSV_XS`

Run:
`$ ./biglist_filter_kentekens.pl`

## Node.Js (v9.6.1)
Requires module 'fast-csv'
`./node_modules/` folder bundled.

Run:
```
$ cd CSV.js
$ ./biglist_filter_kentekens.node.js
```

## PHP (PHP 7.2.3)
Run:
`$ ./biglist_filter_kentekens.php`

## Python (Python 2.7.10)
Run:
`$ ./biglist_filter_kentekens.py`

## Ruby (ruby 2.3.3p222 (2016-11-21 revision 56859) [universal.x86_64-darwin17])
Run:
`$ ./biglist_filter_kentekens.rb`

### EOF ###
