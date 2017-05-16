<?php
namespace Grav\Plugin;

use Grav\Common\Grav;
use Grav\Common\Plugin;
use Grav\Common\Page\Page;
use RocketTheme\Toolbox\Event\Event;

require('Utilities.php');
use DirectoyListing\Utilities;

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

    public function onTwigExtensions()
    {
        require_once(__DIR__ . '/twig/DirectoryListingTwigExtension.php');
        $this->grav['twig']->twig->addExtension(new DirectoryListingExtension());
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
            'onTwigPageVariables' => ['buildOutput', 0]
        ]);
    }

    /**
     * Builds hierarchical HTML list of files/folders
     * @return string Hierarchical HTML List
     */
    public function buildOutput(Event $event)
    {
        $config = (array) $this->config->get('plugins');
        $config = $config['directorylisting'];
        $page = $event['page'];
        $config = $this->mergeConfig($page);
        $config['locator'] = $this->grav['locator'];
        $config['pages_path'] = $config['locator']->findResource('page://', true);
        $utility = new Utilities();

        if ($config['builtin_css']) {
            $this->grav['assets']->addCss('plugin://directorylisting/css/directorylisting.css');
        }
        if ($config['builtin_js']) {
            $this->grav['assets']->addJs('jquery');
            $this->grav['assets']->addJs('plugin://directorylisting/js/directorylisting.js');
        }

        if ($config['include_additional']) {
            $pages = $this->grav['pages']->instances();
        } else {
            $pages = false;
        }
        $structure = $utility->buildDirectoryList($config, $this->grav['page'], $pages);

        $this->grav['twig']->twig_vars['directorylisting'] = $structure;
    }
}
