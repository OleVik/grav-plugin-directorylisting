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
    protected function dirToArray($dir, $exclude = array())
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
    protected function recursiveArrayToList($array, $root, $uid = '', $links = false, $css = false)
    {
        echo '<ul class="directorylisting">';
        foreach ($array as $key => $value) {
            $url = $this->grav['page']->rawRoute();
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
                $this->recursiveArrayToList($value, $root, $uid . '_' . $key, $links, $css);
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
        $config['locator'] = $this->grav['locator'];
        $config['pages_path'] = $config['locator']->findResource('page://', true);
        $page = $this->grav['page'];
        $css = $config['builtin_css'];
        $javascript = $config['builtin_js'];
        $links = $config['links'];
        if (isset($config) && $config['enabled']) {
            $path = $page->path();
            $title = $page->header()->title;
            if ($css) {
                $this->grav['assets']->addCss('plugin://directorylisting/css/directorylisting.css');
            }
            if ($javascript) {
                $this->grav['assets']->addJs('jquery');
                $javascript_inline = "$(document).ready(function() {\n";
                if ($config['level']) {
                    for ($i = 1; $i <= $config['level']; $i++) {
                        $level = 'ul.directorylisting ';
                        $level = str_repeat($level, $i);
                        $javascript_inline .= "$('div.directorylist > $level> li.item.directory > input[type=\"checkbox\"]').prop('checked', true);\n";
                        $javascript_inline .= "$('div.directorylist > $level> ul.directorylisting').show();\n";
                    }
                } else {
                    $javascript_inline .= "$('div.directorylist > ul.directorylisting > li.item.directory > input[type=\"checkbox\"]').prop('checked', true);\n";
                    $javascript_inline .= "$('div.directorylist > ul.directorylisting > ul.directorylisting').show();\n";
                }
                $javascript_inline .= "$('div.directorylist li.item.directory input[type=\"checkbox\"]').change(function() {\n";
                $javascript_inline .= "if ($(this).is(\":checked\")) {\n";
                $javascript_inline .= "$(this).parent().next(\"ul.directorylisting\").show();\n";
                $javascript_inline .= "} else {\n";
                $javascript_inline .= "$(this).parent().next(\"ul.directorylisting\").hide();\n";
                $javascript_inline .= "}\n";
                $javascript_inline .= "});\n";
                $javascript_inline .= "})\n";
                $this->grav['assets']->addInlineJs($javascript_inline);
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
            $output = '';
            $items = array($title => $this->dirToArray($path, $excludeFiles));

            if ($config['include_additional']) {
                $instances = $this->grav['pages']->instances();
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
                echo '<div class="directorylist">';
                if (count($items) > 1) {
                    echo '<ul class="directorylisting">';
                    echo '<li class="item directory">';
                    if ($css) {
                        echo '<input type="checkbox" id="' . strtolower($key) . '" />';
                        echo '<label for="' . strtolower($key) . '">' . $key . '</label>';
                    } else {
                        echo $key;
                    }
                    echo '</li>';
                }
                $this->recursiveArrayToList($item, $path, strtolower($key), $links, $css);
                if (count($items) > 1) {
                    echo '</ul>';
                }
                echo '</div>';
                $output .= ob_get_contents();
                ob_end_clean();
            }

            $this->grav['twig']->twig_vars['directorylisting'] = $output;
        }
    }
}
