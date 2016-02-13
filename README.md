# DIY Posts Shortcode
Allows to insert post lists using inline code:

```
[posts q="cat=39&posts_per_page=3&meta_key=_thumbnail_id"]
<a href="{get_the_permalink}">
{if has_post_thumbnail}{get_the_post_thumbnail 0 medium}<br>{/if}
{get_the_title}</a><br>
[/posts]
```

and theme templates:
```
[posts q="cat=2&posts_per_page=3" item="content-aside"]
```

[Read full documentation](https://d.vensko.net/diy-posts-shortcode)