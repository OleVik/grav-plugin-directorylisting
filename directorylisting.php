<?php
namespace Grav\Plugin;

use Grav\Common\Grav;
use Grav\Common\Plugin;
use Grav\Common\Page\Page;
use Grav\Common\Page\Media;
use Grav\Common\Page\Collection;
use RocketTheme\Toolbox\Event\Event;

require_once __DIR__ . '/Utilities.php';
use DirectoryListing\Utilities;

/**
 * Builds hierarchical HTML-list from page-structure
 *
 * Creates a hierarchy of pages through Twig,
 * including child-pages and media,
 * stylized as a collapsible tree-structure.
 *
 * Class DirectoryListingPlugin
 * @package Grav\Plugin
 * @return string Hierarchical HTML-List
 * @license MIT License by Ole Vik
 */
class DirectoryListingPlugin extends Plugin
{
    /**
     * Initialize plugin and subsequent events
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return [
            'onPluginsInitialized' => ['onPluginsInitialized', 0],
            'onTwigExtensions' => ['onTwigExtensions', 0]
        ];
    }

    /**
     * Register events with Grav
     * @return void
     */
    public function onPluginsInitialized()
    {
        if ($this->isAdmin()) {
            return;
        }
        $this->enable([
            'onAssetsInitialized' => ['init', 0],
            'onTwigPageVariables' => ['output', 0],
            'onTwigSiteVariables' => ['output', 0]
        ]);
    }

    /**
     * Register Twig-extension with Grav
     * @return void
     */
    public function onTwigExtensions()
    {
        require_once __DIR__ . '/twig/DirectoryListingTwigExtension.php';
        $this->grav['twig']->twig->addExtension(new DirectoryListingExtension());
    }

    /**
     * Initialize plugin assets
     * @return void
     */
    public function init()
    {
        $config = (array) $this->config->get('plugins.directorylisting');
        if ($config['builtin_css']) {
            $this->grav['assets']->addCss('plugin://directorylisting/css/directorylisting.css');
            $this->grav['assets']->addCss('plugin://directorylisting/css/metisMenu.min.css');
            $this->grav['assets']->addCss('plugin://directorylisting/css/mm-folder.css');
        }
        if ($config['builtin_js']) {
            $this->grav['assets']->addJs('jquery');
            $this->grav['assets']->addJs('plugin://directorylisting/js/metisMenu.min.js');
            $this->grav['assets']->addJs('plugin://directorylisting/js/directorylisting.js');
        }
    }

    /**
     * Builds hierarchical HTML-list of pages and media
     * @return string HTML-List
     */
    public function output()
    {
        $twig_vars = $this->grav['twig']->twig_vars;
        if (!isset($twig_vars['directorylisting']) || !empty($twig_vars['directorylisting'])) {
            $config = (array) $this->config->get('plugins.directorylisting');
            $page = $this->grav['page'];
            $route = $page->route();
            $config = $this->mergeConfig($page);
            $utility = new Utilities($config);
            $list = $utility->build($route);
            $this->grav['twig']->twig_vars['directorylisting'] = '<div class="directorylist">' . $list . '</div>';
        }
    }
}
