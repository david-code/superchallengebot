# superchallengebot
surrealix's Super Challenge bot. Records books read and movies seen using Twitter messages.

This software is used to create a "Twitterbot" which will track your time and page count on foriegn films and books.

In order to get it to load the first page you'll need to update the db.sql file with the
consumer_key
consumer_secret_key
oauth_token
oauth_secret_token

which you'll obtain from twitter. You can get instructions here:

https://dev.twitter.com/oauth/overview/faq

# Dependencies

- curl extension

Unit tests:

- dom extension
- json extension

# Tweet Parsing Rules

## Registration

Tags: #register, #swedish/#french/#it/#ru

Example:
```
    @langchallenge I'm going to #register and study #French
```

## Withdrawing

Tags: #giveup, [#language]

Example:
```
    @langchallenge #Swedish is too hard. I #giveup
```

## Reading

Tags: #book/#read, [#swedish/#french/#it/#ru/etc], [123 page/s], ["book title"]

Example:
```
    @langchallenge I just read a #book
    @langchallenge I just finished reading a #sv #book with 60 pages. It was called "The Invisible Book" and I keep losing it.
```

## Watching

Tags: #film/#movie/#watch(ed)(ing)/#listen(ed)(ing)/#audio/#radio, [#swedish/#french/#it/#ru/etc], [123 min(ute)/h(ou)r/s], ["film title"]

Example:
```
    @langchallenge I just watched a #movie #ru
    @langchallenge I just saw the first 20 min of a #film ("The endless snowstorm"). Really strange Indie movie!
```

## Editing

You have to reply to the tweet you want to change.

Tags: #edit(ed)/#update(d), [123 page(s)/minute(s)/etc], ["updated title of item"]

Example:
```
    @langchallenge The author wrote another chapter and #updated the book online, now I've read 260 pages total!
    @langchallenge I misread the title and need to #edit it to be "How to FIND your monocle in 30 seconds."
```

## Deleting

You have to reply to the tweet you want to delete.

Tags: #undo(ne)/#delete(d)

Example:
```
    @langchallenge I lied about this update and now feel very guilty, so I'm to #undo it.
```
