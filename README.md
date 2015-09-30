# HumanName
Parse human names into each individual component. HumanName is a port of [derek73's][1]
Python package, [nameparser][2]. Originally, I had written a small Python script using
the [name_tools][3] package but due to server support needed to port this over to PHP
and found [nameparser][2] more in depth and a better fit for the job.

There is still a lot of work I need to put into this as I have not coded in PHP for
quite some time, but it is slowly getting there. First priority is documenting the code
and writing proper tests.

## HumanName Example
```php
require_once('./HumanName.php');
# need to convert to Use\HumanName;

$name = new HumanName('Dr. Juan Q. Xavier de la Vega III (Doc Vega)');

print $name->first().' '.$name->last();
# Juan de la Vega

print $name->title().' '.$name->last();
# Dr. de la Vega

print_r($name->compnents());
# Array
# (
#     [title] => Dr.
#     [first] => Juan
#     [middle] => Q. Xavier
#     [last] => de la Vega
#     [suffix] => III
#     [nickname] => Doc Vega
# )
```
The main use of this will be to convert .txt files to .csv files, so I added the
HumanName::delimited() method not it's Python's counterpart.
```php
require_once('./HumanName.php');

$name = new HumanName('Dr. Juan Q. Xavier de la Vega III (Doc Vega)');

# The method supports a custom delimiter as well as joining additional data.
# HumanName::delimited(delimiter=',', additional_data=array());

print slice($name->delimited(), 1);
# Dr.,Juan,Q. Xavier,de la Vega,III,Doc Vega
```

## Supported Formats
Like it's Python counterpart, there are three different formats supported in HumanName:
- Title Firstname "Nickname" Middle Middle Lastname Suffix
- Lastname [Suffix], Title Firstname (Nickname) Middle Middle[,] Suffix [, Suffix]
- Title Firstname M Lastname [Suffix], Suffix [Suffix] [, Suffix]

I modified the nickname regex slightly to also look for single quotes `'` so the
following hold true for nicknames:e
- `(nickname)`
- `'nickname'`
- `"nickname"`


[1]: https://github.com/derek73
[2]: https://github.com/derek73/python-nameparser
[3]: https://github.com/jamesturk/name_tools
