<?php
namespace DirectoryListing;

use Grav\Common\Grav;
use Grav\Common\Plugin;
use Grav\Common\Page\Page;
use Grav\Common\Page\Media;
use Grav\Common\Page\Collection;

class Utilities
{
    /**
     * Plugin configuration
     * @var array
     */
    protected $config;

    /**
     * Instantiate DirectoyListing Utilities
     */
    public function __construct($config)
    {
        $this->config = $config;
    }

    /**
     * Sorts out configuration and initiates building
     * @param string $route Route to page
     * @return string HTML-list from page-structure
     */
    public function build($route)
    {
        $config = $this->config;
        $tree = $this->buildTree($route);
        if ($config['include_additional']) {
            foreach ($config['include_additional'] as $include) {
                if (is_array($include)) {
                    $include = $this->buildTree($include, '@page.self');
                    $tree = array_merge($tree, $include);
                }
            }
        }
        if ($config['exclude_additional']) {
            foreach ($config['exclude_additional'] as $exclude) {
                if (is_array($exclude)) {
                    $this->arrayExcept($tree, array($exclude));
                }
            }
        }
        if ($tree) {
            $list = $this->buildList($tree);
            return $list;
        } else {
            return false;
        }
    }

    /**
     * Creates page-structure recursively
     * @param string $route Route to page
     * @param integer $depth Reserved placeholder for recursion depth
     * @return array Page-structure with children and media
     */
    public function buildTree($route, $mode = false, $depth = 0)
    {
        $config = $this->config;
        $page = Grav::instance()['page'];
        $depth++;
        $mode = '@page.self';
        if ($depth > 1) {
            $mode = '@page.children';
        }
        if ($config['max_depth'] == 0) {
            $max_depth = 100;
        } else {
            $max_depth = (int) $config['max_depth'];
        }
        $pages = $page->evaluate([$mode => $route]);
        $pages = $pages->published()->order($config['order']['by'], $config['order']['dir']);
        $paths = array();
        if ($depth <= $max_depth) {
            foreach ($pages as $page) {
                if ($config['exclude_modular'] && isset($page->header()->content['items'])) {
                    if ($page->header()->content['items'] == '@self.modular') {
                        continue;
                    }
                }
                $route = $page->rawRoute();
                $paths[$route]['depth'] = $depth;
                $paths[$route]['title'] = $page->title();
                $paths[$route]['route'] = $route;
                $paths[$route]['url'] = $page->url();
                $paths[$route]['name'] = $page->name();
                if (!empty($paths[$route])) {
                    $children = $this->buildTree($route, $mode, $depth);
                    if (!empty($children)) {
                        $paths[$route]['children'] = $children;
                    }
                }
                $media = new Media($page->path());
                foreach ($media->all() as $filename => $file) {
                    $paths[$route]['media'][$filename] = $file;
                }
            }
        }
        if (!empty($paths)) {
            return $paths;
        } else {
            return null;
        }
    }

    /**
     * Creates HTML-lists recursively
     * @param array $tree Page-structure with children and media
     * @param integer $depth Reserved placeholder for recursion depth
     * @return string HTML-list
     */
    public function buildList($tree, $depth = 0)
    {
        $config = $this->config;
        $depth++;
        if ($config['builtin_css'] && $config['builtin_js'] && $depth == 1) {
            $list = '<ul class="metismenu metisFolder">';
        } else {
            $list = '<ul>';
        }
        foreach ($tree as $route => $page) {
            $list .= '<li class="directory';
            if ($page['depth'] <= (int) $config['level']) {
                $list .= ' active';
            }
            $list .= '">';
            if ($config['builtin_css'] && $config['builtin_js']) {
                $list .= '<a href="#" aria-expanded="true" class="has-arrow">' . $page['title'] . '</a>';
            } else {
                if ($config['links']) {
                    $list .= '<a href="' . $page['url'] . '">' . $page['title'] . '</a>';
                } else {
                    $list .= $page['title'];
                }
            }
            if (!$config['exclude_main']) {
                if ($config['links']) {
                    $list .= '<ul><li class="file page"><a href="' . $page['url'] . '">';
                    $list .= $page['name'];
                    $list .= '</a></li></ul>';
                } else {
                    $list .= '<ul><li class="file page">';
                    $list .= $page['name'];
                    $list .= '</li></ul>';
                }
            }
            if (isset($page['children'])) {
                $list .= $this->buildList($page['children'], $depth);
            }
            if (isset($page['media'])) {
                if ($config['showfiles']) {
                    $list .= '<ul>';
                    foreach ($page['media'] as $filename => $file) {
                        if ($config['links']) {
                            $list .= '<li class="file ' . $file->items()['type'] . '">';
                            $list .= '<a href="' . $file->url() . '">' . $filename . '</a>';
                            $list .= '</li>';
                        } else {
                            $list .= '<li class="file ' . $file->items()['type'] . '">' . $filename . '</li>';
                        }
                    }
                    $list .= '</ul>';
                }
                $list .= '</li>';
            }
        }
        $list .= '</ul>';
        return $list;
    }

    /**
     * Removes keys from an array
     * @param array $array Array to remove keys from
     * @param array $except Keys to remove
     * @return void
     * @see http://stackoverflow.com/a/37082098/603387
     */
    public function arrayExcept(&$array, $except)
    {
        foreach ($array as $key => $value) {
            if (in_array($key, $except, true)) {
                unset($array[$key]);
            } else {
                if (is_array($value)) {
                    $this->arrayExcept($array[$key], $except);
                }
            }
        }
        return;
    }
}
