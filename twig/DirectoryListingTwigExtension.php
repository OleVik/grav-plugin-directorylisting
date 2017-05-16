<?php
namespace Grav\Plugin;

use Grav\Common\Grav;
use Grav\Common\Plugin;
use Grav\Common\Page\Page;
use RocketTheme\Toolbox\Event\Event;

use DirectoyListing\Utilities;

class DirectoryListingExtension extends \Twig_Extension
{
    public function getName()
    {
        return 'DirectoryListingExtension';
    }

    public function getFunctions()
    {
        return [
            new \Twig_SimpleFunction('directorylisting', function ($settings = false) {
                return $this->directorylistingFunction($settings);
            })
        ];
    }

    public function directorylistingFunction($settings = false)
    {
        $config = Grav::instance()['config']->get('plugins.directorylisting');
        Grav::instance()['assets']->addInlineJs("console.log('asd2')");
        $config['locator'] = Grav::instance()['locator'];
        $config['pages_path'] = $config['locator']->findResource('page://', true);
        if ($settings) {
            foreach ($settings as $setting => $value) {
                $config[$setting] = $value;
            }
        }
        $utility = new Utilities();

        if ($config['builtin_css']) {
            Grav::instance()['assets']->addCss('plugin://directorylisting/css/directorylisting.css');
        }
        if ($config['builtin_js']) {
            Grav::instance()['assets']->addJs('jquery');
            Grav::instance()['assets']->addJs('plugin://directorylisting/js/directorylisting.js');
        }

        $page = Grav::instance()['page'];
        if ($config['include_additional']) {
            $pages = Grav::instance()['pages']->instances();
        } else {
            $pages = false;
        }
        $structure = $utility->buildDirectoryList($config, $page, $pages);
        return $structure;
    }
}
