import fileinput
import random
import re
import unicodedata

import click

def encode(data, encoding='utf-8', *errors):
    '''
    encode returned json object to specified `encoding`

    :param data: object to encode.
    :param encoding: encoding to use.
    :param errors: errors used for str.encode(encoding[. errors])

    '''
    if isinstance(data, dict):
        return {encode(k): encode(v) for k, v in data.iteritems()}

    if isinstance(data, list):
        return [encode(i) for i in data]

    if isinstance(data, unicode):
        return data.encode(encoding, *errors)

    return data

def get_name(fp):
    line = next(fp)
    for i, fp in enumerate(fp):
        if random.randrange(i + 2):
            continue
        line = fp

    return line.strip()

def first_name(chance=10):
    out = get_name(fileinput.input('first_name.txt'))
    if chance != 0 and random.randrange(chance) == 0:
        out = '{} & {}'.format(out, first_name(0))

    return out

def middle_name(chance=10, include_chance=10):
    out = first_name()
    if random.randrange(chance) != 0:
        out = '{}.'.format(out[0])

    if random.randrange(include_chance) == 0:
        return out
    
    return ''

def last_name(chance=10):
    out = get_name(fileinput.input('last_name.txt'))
    if chance != 0 and random.randrange(chance) == 0:
        out = '{}-{}'.format(out, last_name(0))

    return out

def get_item(filename):
    fp = fileinput.input(filename)

    line = next(fp)
    for i, fp in enumerate(fp):
        if random.randrange(i + 2):
            continue
        line = fp

    return line.strip()

#Title Firstname "Nickname" Middle Middle Lastname Suffix
#Lastname [Suffix], Title Firstname (Nickname) Middle Middle[,] Suffix [, Suffix]
#Title Firstname M Lastname [Suffix], Suffix [Suffix] [, Suffix]

def style1():
    title = get_title()
    first = get_first_name()
    middle = get_middle_name()
    last = get_last_name()
    suffix = get_suffix()

    email = get_email(first, last)

    return '{}{}{}{}{} {}'.format(title, first, middle, last, suffix, email)

def style2():
    title = get_title()
    first = get_first_name()
    middle = get_middle_name()
    last = get_last_name()
    suffix = get_suffix()

    last_suffix = '{}{}'.format(last, suffix).strip()
    email = get_email(first, last)

    return '{}, {}{}{} {}'.format(last_suffix, title, first, middle, email)

def style3():
    title = get_title()

    return ''

def get_title(chance=10):
    if random.randrange(chance) == 0:
        out = get_item('titles.txt').capitalize()
        if random.randrange(chance) == 0:
            out += ' {}'.format(get_item('titles.txt').capitalize())
        return out + ' '
    return ''

def get_first_name(chance=10):
    out = get_item('first_name.txt')
    if chance != 0 and random.randrange(chance) == 0:
        conj = random.choice(['&', 'and'])
        out = '{} {} {}'.format(out, conj, get_first_name(0))

    if '&' not in out and 'and' not in out:
        if chance != 0 and random.randrange(chance) == 0:
            out = '{} "{}"'.format(out, get_first_name(0).strip())

    return out + ' '

def get_middle_name(chance=10):
    out = get_item('first_name.txt')
    if random.randrange(chance) == 0:
        out = '{}.'.format(out[0])

    if chance != 0 and random.randrange(chance) == 0:
        out = '{} {}'.format(out, get_first_name(0))

    return out + ' '

def get_last_name(chance=10):
    out = get_item('last_name.txt')
    
    if chance != 0 and random.randrange(chance) == 0:
        out = '{} {}'.format(get_item('prefixes.txt').capitalize(), out)

    if chance != 0 and random.randrange(chance) == 0:
        out = '{}-{}'.format(out, get_last_name(0))

    return out + ' '

def get_suffix(chance=10):
    out = ''
    for i in xrange(chance):
        if random.randrange(chance) == 0:
            out += '{} '.format(get_item('suffixes.txt'))

    return out + ' ';

def get_email(first, last):
    first = first.split(' ')[0].strip()
    last = re.sub(' ', '', last.strip())
    sep = random.choice('-_. +')
    return '{}{}{}@test{}'.format(first, sep, last, get_item('tld.txt'))



@click.command()
@click.option('--output', prompt='Output')
@click.option('--count', default=1, prompt='How many names?')
def generate(output, count):
    output = '../{}.txt'.format(output)
    with open(output, 'w') as fp:
        for i in xrange(count):
            name = random.choice([style1, style2])()
            fp.write(re.sub(' +', ' ', name).lower() + '\n')

            click.clear()
            print '{} names generated'.format(i)


if __name__ == '__main__':
    click.clear()
    generate()
