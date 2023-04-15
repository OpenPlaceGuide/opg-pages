# Merging the fonts

*Also published on https://stackoverflow.com/a/76023115*

As a workaround I merged the font myself as described [by satish][1].

```
apt install python3-nototools fonttools
cp /usr/lib/python3/dist-packages/nototools/merge_fonts.py ~/merge_fonts.py
```

Use this fontlist

```
# file names to be merged
files = [
    "NotoSans-Bold.ttf",
    "NotoSansEthiopic-Bold.ttf",
]
```

Then execute

```
PYTHONPATH='.' python3 ~/merge_fonts.py -d /usr/share/fonts/truetype/noto/ -o ~/NotoSansWithEthiopic.ttf
```

Using this font in GDlib works.


[1]: https://satish.com.in/20211205/
