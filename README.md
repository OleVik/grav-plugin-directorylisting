# [Grav](http://getgrav.org/) Directory Listing Plugin

Returns a hierarchy of files below the page through Twig, stylized as a collapsible tree-structure:

![Directory Listing](./directorylisting.png)

## Installation and Configuration

1. Download the zip version of [this repository](https://github.com/OleVik/grav-plugin-directorylisting) and unzip it under `/your/site/grav/user/plugins`.
2. Rename the folder to `directorylisting`.

You should now have all the plugin files under

    /your/site/grav/user/plugins/directorylisting

The plugin is enabled by default, and can be disabled by copying `user/plugins/directorylisting/directorylisting.yaml` into `user/config/plugins/directorylisting.yaml` and setting `enabled: false`.

### Options

| Variable | Default | Options | Note |
|----------------|---------|-------------------|--------------------------------------------------------------------------|
| `enabled` | `true` | `true` or `false` | Enables or disables plugin entirely. |
| `level` | 1 | (int) 0-10 | Initial level of folders to expand on load. |
| `exclude_main` | `true` | `true` or `false` | Excludes the page-file, ie. the Markdown-.file, from the tree-structure. |
| `exclude_modular` | `true` | `true` or `false` | Excludes modular pages, ie. the modular folders from the tree-structure. |
| `exclude_additional` | null | List of files/folders | Excludes additional files/foldes from the tree-structure, as a YAML list. |
| `include_additional` | null | List of files/folders | Additional folders to include in the tree-structure, as a YAML list. |
| `links` | `true` | `true` or `false` | Enables or disables links on file names. |
| `builtin_css` | `true` | `true` or `false` | Enables or disables the plugin's built-in CSS. |
| `builtin_js` | `true` | `true` or `false` | Enables or disables the plugin's built-in JavaScript. |

By default all files and files are recursively traversable in the same folder as the page, with the first level of folders expanded. Navigation is done by **clicking the folder-name**, which behind-the-scenes checks a checkbox that reveals the files in the folder (and subdirectories). This requires the small piece of JavaScript to work, as CSS as of yet does not have a stable parent selector.

Disabling `builtin_css` and `builtin_js` returns a simple hierarchical unordered HTML-list, which can be styled manually through your theme. With `exclude_main` enabled, the default Markdown-file is hidden, and by default the plugin also hides dotfiles and hidden folders. Disabling `links` will return just filenames, rather than filenames wrapped in a link to the file. The `level`-option sets how many levels deep the folders should expand on load.

Additional files or folders can be **excluded** by passing a list to `exclude_additional`. Additional **folders** can be **included** by passing a list to `include_additional`, which should match folder-names in /user/pages/, not page-titles.

**Note:** As with Grav itself, you should avoid a large amount of subfolders and files underneath /pages. As the plugin recursively iterates below any page whose template uses the Twig-tag, a large amount of files (numerically, not in size), could slow performance. If the site is cached, this is not noticeable even with thousands of files.

### Example

Simply place the below tag in any Twig-template you wish the tree-structure to be available on.

```
{{ directorylisting }}
```

MIT License 2017 by [Ole Vik](http://github.com/olevik).
