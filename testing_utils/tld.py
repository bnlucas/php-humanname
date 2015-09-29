import requests

from pattern.web import DOM, plaintext


def load_dom(url):
    r = requests.get(url)

    if r.status_code == 200:
        return DOM(r.content)

    return None


dom = load_dom('https://iwantmyname.com/domains/domain-name-registration-list-of-extensions')
with open('tld.txt', 'w') as f:
    for a in dom.by_tag('a.tld'):
        try:
            f.write(plaintext(a.content).lower() + '\n')
        except UnicodeEncodeError:
            continue;
