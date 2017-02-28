<?php
namespace Grav\Plugin;

use Grav\Common\Grav;
use Grav\Common\Plugin;
use Grav\Common\Page\Page;
use RocketTheme\Toolbox\Event\Event;

/**
 * Builds hierarchical HTML list of files/folders
 *
 * Returns a hierarchy of files below the page through Twig,
 * stylized as a collapsible tree-structure.
 *
 * Class DirectoryListingPlugin
 * @package Grav\Plugin
 * @return string Hierarchical HTML List
 * @license MIT License by Ole Vik
 */
class DirectoryListingPlugin extends Plugin
{
    /**
     * Registers events with Grav
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return [
            'onTwigPageVariables' => ['onTwigPageVariables', 0]
        ];
    }

    /**
     * Directory-structure to array, recursively
     * @param string $dir Path to folder
     * @param array $excludes List of files/folders to exclude
     * @return array Directory-structure
     * @see http://php.net/manual/en/function.scandir.php#110570
     */
    protected function dirToArray($dir, $excludes = array())
    {
        $result = array();
        $contents = array_filter(scandir($dir), function ($item) {
            return $item[0] !== '.';
        });
        $exclude = array();
        if ($excludes) {
            $exclude = array_merge($exclude, $excludes);
        }
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
    protected function recursiveArrayToList($array, $root, $links = false, $css = false)
    {
        echo '<ul class="directorylisting">';
        foreach ($array as $key => $value) {
            $url = $this->grav['page']->rawRoute();
            $location = str_replace($root, '', $value);
            if (is_array($value)) {
                if (!empty($value)) {
                    echo '<li class="item directory">';
                    if ($css) {
                        echo '<input type="checkbox" id="' . $key . '" />';
                        echo '<label for="' . $key . '">' . $key . '</label>';
                    } else {
                        echo $key;
                    }
                    echo '</li>';
                }
                $this->recursiveArrayToList($value, $root, $links, $css);
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

    /**
     * Builds hierarchical HTML list of files/folders
     * @return string Hierarchical HTML List
     */
    public function onTwigPageVariables()
    {
        /* Check if Admin-interface */
        if ($this->isAdmin()) {
            return;
        }

        $config = (array) $this->config->get('plugins');
        $config = $config['directorylisting'];
        $page = $this->grav['page'];
        $css = $config['builtin_css'];
        $javascript = $config['builtin_js'];
        $links = $config['links'];
        if (isset($config) && $config['enabled']) {
            $path = $page->path();
            if ($css) {
                $this->grav['assets']->addCss('plugin://directorylisting/css/directorylisting.css');
            }
            if ($javascript) {
                $this->grav['assets']->addJs('jquery');
                $this->grav['assets']->addJs('plugin://directorylisting/js/directorylisting.js');
            }
            $excludeFiles = array();
            if ($config['exclude_main']) {
                $excludeFiles[] = $page->name();
            }
            if ($config['exclude_modular']) {
                if (isset($page->header()->content['items'])) {
                    if ($page->header()->content['items'] == '@self.modular') {
                        foreach ($page->header()->content['order']['custom'] as $module) {
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
            $items = $this->dirToArray($path, $excludeFiles);

            ob_start();
            echo '<div class="directorylist">';
            $this->recursiveArrayToList($items, $path, $links, $css);
            echo '</div>';
            $output = ob_get_contents();
            ob_end_clean();

            $this->grav['twig']->twig_vars['directorylisting'] = $output;
        }
    }
}
