<?php
namespace DirectoyListing;

class Utilities
{

    /**
     * Unique identifier for HTML-structure
     * @var int
     */
    protected $seed;

    /**
     * Instantiate DirectoyListing Utilities
     */
    public function __construct()
    {
        $this->seed = rand(1000, 9999);
    }

    /**
     * Builds hierarchical HTML list of files/folders
     * @return string Hierarchical HTML List
     */
    public function buildDirectoryList($config, $page, $pages = false)
    {
        $css = $config['builtin_css'];
        $javascript = $config['builtin_js'];
        $links = $config['links'];
        if (isset($config) && $config['enabled']) {
            $path = $page->path();
            $title = $page->header()->title;
            $excludeFiles = array();
            if ($config['exclude_main']) {
                $excludeFiles[] = $page->name();
            }
            if ($config['exclude_modular']) {
                if (isset($page->header()->content['items'])) {
                    if ($page->header()->content['items'] == '@self.modular') {
                        $children = array_filter(array_keys($this->dirToArray($path)));
                        foreach ($children as $module) {
                            $excludeFiles[] = $module;
                        }
                    }
                }
            }
            if ($config['exclude_additional']) {
                foreach ($config['exclude_additional'] as $fileToExclude) {
                    $excludeFiles[] = $fileToExclude;
                }
            }
            $output = '';
            $items = array($title => $this->dirToArray($path, $excludeFiles));

            if ($config['include_additional']) {
                $instances = $pages;
                foreach ($config['include_additional'] as $fileToInclude) {
                    $includePath = $config['pages_path'] . '/' . $fileToInclude;
                    if (in_array($includePath, array_keys($instances))) {
                        $title = $instances[$includePath]->header()->title;
                        $include = $this->dirToArray($includePath);
                        $items[$title] = $include;
                    }
                }
            }
            foreach ($items as $key => $item) {
                ob_start();
                $seed = rand(1000, 9999);
                echo '<div class="directorylist" id="directorylist_' . $seed . '">';
                if (count($items) > 1) {
                    echo '<ul class="directorylisting">';
                    echo '<li class="item directory">';
                    if ($css) {
                        echo '<input type="checkbox" id="' . $seed . '_' . strtolower($key) . '" />';
                        echo '<label for="' . $seed . '_' . strtolower($key) . '">' . $key . '</label>';
                    } else {
                        echo $key;
                    }
                    echo '</li>';
                }
                $this->recursiveArrayToList($page->rawRoute(), $item, $path, strtolower($key), $links, $css);
                if (count($items) > 1) {
                    echo '</ul>';
                }
                echo '</div>';
                $output .= ob_get_contents();
                ob_end_clean();
            }

            return $output;
        }
    }

    /**
     * Directory-structure to array, recursively
     * @param string $dir Path to folder
     * @param array $excludes List of files/folders to exclude
     * @return array Directory-structure
     * @see http://php.net/manual/en/function.scandir.php#110570
     */
    public function dirToArray($dir, $exclude = array())
    {
        $result = array();
        $contents = array_filter(scandir($dir), function ($item) {
            return $item[0] !== '.';
        });
        foreach ($contents as $value) {
            if (!in_array($value, $exclude)) {
                if (is_dir($dir . '/' . $value)) {
                    $result[$value] = $this->dirToArray($dir . '/' . $value, $exclude);
                } else {
                    $result[] = $dir . '/' . $value;
                }
            }
        }
        return $result;
    }

    /**
     * Directory-structure to HTML list
     * @param array $array Directory-structure
     * @param string $root Root path to files
     * @param boolean $links Include links to files
     * @param boolean $css Use plugin's styles
     * @return void
     * @see http://stackoverflow.com/a/36334789/603387
     */
    public function recursiveArrayToList($url, $array, $root, $uid = '', $links = false, $css = false)
    {
        echo '<ul class="directorylisting">';
        foreach ($array as $key => $value) {
            $location = str_replace($root, '', $value);
            if (is_array($value)) {
                if (!empty($value)) {
                    echo '<li class="item directory">';
                    if ($css) {
                        echo '<input type="checkbox" id="' . $uid . '_' . $key . '" />';
                        echo '<label for="' . $uid . '_' . $key . '">' . $key . '</label>';
                    } else {
                        echo $key;
                    }
                    echo '</li>';
                }
                $this->recursiveArrayToList($url, $value, $root, $uid . '_' . $key, $links, $css);
            } else {
                $parts = pathinfo($value);
                if ($links) {
                    echo '<li class="item file"><a href="' . $url . $location . '">' . $parts['basename'] . '</a></li>';
                } else {
                    echo '<li class="item file">' . $parts['basename'] . '</li>';
                }
            }
        }
        echo '</ul>';
    }
}
