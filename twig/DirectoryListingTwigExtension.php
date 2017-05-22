<?php
namespace Grav\Plugin;

use Grav\Common\Grav;
use Grav\Common\Plugin;
use Grav\Common\Page\Page;
use RocketTheme\Toolbox\Event\Event;
use DirectoryListing\Utilities;

/**
 * Builds hierarchical HTML-list from page-structure
 *
 * Creates a hierarchy of pages through Twig,
 * including child-pages and media,
 * stylized as a collapsible tree-structure.
 *
 * Class DirectoryListingExtension
 * @package Grav\Plugin
 * @return string Hierarchical HTML-List
 * @license MIT License by Ole Vik
 */
class DirectoryListingExtension extends \Twig_Extension
{
    /**
     * Declare extension name for backwards-compatibility
     * @return string Extension name
     */
    public function getName()
    {
        return 'DirectoryListingExtension';
    }

    /**
     * Returns directorylisting-function
     * @return Twig_SimpleFunction
     */
    public function getFunctions()
    {
        return [
            new \Twig_SimpleFunction('directorylisting', function ($settings = false) {
                return $this->directorylistingFunction($settings);
            })
        ];
    }

    /**
     * Sorts out configuration and builds list
     * @param array $settings Configuration to override defaults
     * @return string HTML-list
     */
    public function directorylistingFunction($settings = false)
    {
        $config = Grav::instance()['config']->get('plugins.directorylisting');
        if ($config['builtin_css']) {
            Grav::instance()['assets']->addCss('plugin://directorylisting/css/directorylisting.css');
            Grav::instance()['assets']->addCss('https://maxcdn.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css');
            Grav::instance()['assets']->addCss('plugin://directorylisting/css/metisMenu.min.css');
            Grav::instance()['assets']->addCss('plugin://directorylisting/css/mm-folder.css');
        }
        if ($config['builtin_js']) {
            Grav::instance()['assets']->addJs('jquery');
            Grav::instance()['assets']->addJs('plugin://directorylisting/js/metisMenu.min.js');
            Grav::instance()['assets']->addJs('plugin://directorylisting/js/directorylisting.js');
        }

        if ($settings) {
            foreach ($settings as $setting => $value) {
                $config[$setting] = $value;
            }
        }
        $utility = new Utilities($config);

        $page = Grav::instance()['page'];
        if (isset($settings['route'])) {
            $route = $settings['route'];
        } else {
            $route = $page->route();
        }

        $list = $utility->build($route);
        return '<div class="directorylist">' . $list . '</div>';
    }
}
