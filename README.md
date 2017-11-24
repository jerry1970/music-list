# Music List 

This project was set up to keep track of the sound files stored on hard disks and to generate lists from that.

The files are stored in this structure:
```
root/
  Artist/
    Album/
      sound-file
```

The root can be any directory and does not have to be the root of a disk.
The script just starts looking for artist directories in the given root.

### ml.ini

Some music list properties can be set in `ml.ini` which should be located in the given `root`.

Note: not implemented yet.

Contents of the `ml.ini`:
```.ini
list.title
list.root[] = "..."
list.root[] = "..."
```

A list can have extra roots marked. `ml` only needs to run once and will read the extra given roots. If the current directory should be included, don't forget to include `list.root[] = "."`.

### Artists

Artist directories starting with `.`, `_`, or `$` are skipped.

Main Artist properties:
* key
* alternate key
* display name
* country
* notes
* see also

The full directory name is used as the key, as the file system already makes it unique within this root. It is also used as the default for the display name. To overwrite, use `artist.ini` (see below).

When the Artist directory name ends with `[...]`, the contents between the square brackets are considered to be the country. The key will still be the complete directory name, but the display name will not include the country. So if the direcotry name is `Saga [NL]`, the key is `Saga [NL]`, the display name is `Saga` and the country is `NL`.

#### artist.ini

When the Artist directory has a file `artist.ini`, it can overwrite and add artist properties.

The format of this file is:
```.ini
artist.key = "..."
artist.name = "..."
artist.country = "..."
artist.notes[] = "..."
artist.notes[] = "..."
artist.seeAlso[] = "..."
artist.seeAlso[] = "..."
```

The `key` is stored in the `alternate key` property of the artist. The directory name remains the only real unique key. The alternate key can be used in a seeAlso tag in another `artist.ini`.

The `name` is used as the display name. Country speaks for itself.

Notes and seeAlso can have multiple values and are stored as arrays.

The seeAlso keys link to other artists' keys or alternate keys.

### Albums

Inside an Artist directory are Album directories. Directories starting with `.`, '_', or '$' are skipped.

Main properties of an album:
* key
* date
* title
* seconds

The directory name is the `key` and thanks to the file system will be unique for the artist and is used for sorting.  

The Album directory name can have these formats:
* `title`
* `date, title [option] [option]`

Everything up to the first comma is considered to be the date. For most items this is fine, but when you have two items dated "2017" and "2017-01-01" and you want "2017" to come after "2017-01-01", rename "2017" to "2017-07x" or something, as long as it is sorted the way you want. In the `album.ini` for the "2017-07x" item, you can overwrite the date.

The `date` and `title` are taken from the Album directory name.
If there are any tracks with tags, these are 

The `date` and `title` properties are taken from the Album directory name. When the sound files have tags, they will overwrite these properties.

The `seconds` property is calculated from the time of the individual tags and can be set or overwritten in the `artist.ini` file.

#### album.ini

Contents of `album.ini`:
```.ini
album.date = "..." ; date given in YYYY-mm-dd format
album.title = "..."
album.seconds = "..." ; total number of seconds (decimals are allowed)
album.duration = "..." ; HH:mm:ss format; only used when seconds is not given
album.tracks[] = "1. Song Title (1:23)"
album.option[...] = "..." ; true/1/yes or /false/0/no
album.option[...] = "..." ; value can be anything
```

The `tracks` format can be the title or title and duration:
* `Song Title`
* `Song Title (1:23)`

The options are stored and can be used when generating a list. Often-used options:
* NFT = not for trade
* OFF = officially released material
* DEM = demo release
* SRC = source
* STU = studio recording
* SBD = soundboard recording
* AUD = audience recording
* BRD = radio (off-air) recording

Options can be set in the Album directory name like this:  
`1970, Title [NFT] [OFF] [DEMO]`

Technically, it's possible to set something that does not make sense, like multiple sources:  
`1970, Title [NFT] [OFF] [SBD] [AUD]`
Therefore, options can also be set like this:  
`1970, Title [NFT] [OFF] [SRC=SBD]`
It's just an option, the database does not interpret the meaning.

## Tracks

Inside an Album directory are Tracks, the music files.

For audio files (FLAC, MP3), the tags are read.

## Summary: Properties, Directory Names, Audio Tags

Music List:
* set in `ml.ini`
* set in command line options

Artist:
* `artist.key`: Artist directory name
* `artist.altKey`: can only be set in `artist.ini` and used in other artists' `ini`
* `artist.name`:
    * directory name without optional country
    * or overwritten in `artist.ini`
* `artist.country`:
    * from directory name
    * or overwritten in `ini`
* `artist.notes[]`: can only be set in `ini`

Album:
* `album.key`: full Album directory name
* `album.artist`:
    * same as Artist
    * taken from audio tags (`artist` tag from first audio file)
    * set/overwritten in `album.ini`
* `album.date`:
    * first part of directory name (up till first comma)
    * overwritten by audio tags (`artist` tag of first audio file)
    * overwritten by `album.ini`
* `album.title`:
    * second part of directory name (after first comma up to options)
    * overwritten by audio tags (`album` tag of first audio file)
    * overwritten by `album.ini`
* `album.seconds`:
    * calculated from audio files (if there are any)
    * set or overwritten in `album.ini`
* `album.duration`:
    * calculated from audio files (seconds written in readable format)
    * set or overwritten in `album.ini` (only used when `seconds` is not given)
* `album.option[...]`:
    * option in directory name (`1970-01-01, Album Title [NFT]` to set `NFT` = `true` or `1970-01-01, Album Title [SRC=STU]`)
    * set or overwritten in `album.ini`

Tracks:
* `key`: full Track file name
* `trackNumber`:
    * first part of file name (all digits from the start)
    * set from audio tags
* `title`:
    * second part of file name (after digits and separator, without extension)
    * set from audio tags
* `seconds`:
    * taken from audio properties
    * set/overwritten in `album.ini`
* `duration`:
    * calculated from audio properties
    * set/overwritten in `album.ini`

## Usage

To generate a database from a directory tree:
```.bash
# normale usage
$ ./ml scan

# rebuild the complete database
$ ./ml scan --rebuild
```

## Thanks

Built using the [Parable micro-framework](https://github.com/devvoh/parable) and [getID3](https://github.com/JamesHeinrich/getID3/) library to read audio tags.
