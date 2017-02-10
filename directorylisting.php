<?php
namespace Grav\Plugin;

use Grav\Common\Grav;
use Grav\Common\Plugin;
use Grav\Common\Page\Page;
use RocketTheme\Toolbox\Event\Event;

class DirectoryListingPlugin extends Plugin {
	public static function getSubscribedEvents() {
		return [
			'onTwigSiteVariables' => ['onTwigSiteVariables', 0]
		];
	}
	
	/* Source: http://php.net/manual/en/function.scandir.php#110570 */
	private function dirToArray($dir, $exclude_extra = false) {
		$result = array();
		$cdir = scandir($dir);
		$exclude = array(".", "..");
		if ($exclude_extra) {
			$exclude = array_merge($exclude, $exclude_extra);
		}
		foreach ($cdir as $key => $value) {
			if (!in_array($value, $exclude)) {
				if (is_dir($dir . DIRECTORY_SEPARATOR . $value)) {
					$result[$value] = $this->dirToArray($dir . DIRECTORY_SEPARATOR . $value, $exclude);
				} else {
					$result[] = $dir . DIRECTORY_SEPARATOR . $value;
				}
			}
		}
		return $result;
	}
	
	/* Source: http://stackoverflow.com/a/36334789/603387 */
	private function recursiveArrayToList($array, $root, $links = false, $builtin_css = false) {
		echo '<ul class="directorylisting">';
		foreach ($array as $key => $value) {
			$url = $this->grav['page']->rawRoute();
			$location = str_replace($root, '', $value);
			if (is_array($value)) {
				if (!empty($value)) {
					echo '<li class="item directory">';
					if ($builtin_css) {
						echo '<input type="checkbox" id="' . $key . '" />';
						echo '<label for="' . $key . '">' . $key . '</label>';
					} else {
						echo $key;
					}
					echo '</li>';
				}
				$this->recursiveArrayToList($value, $root, $links, $builtin_css);
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
	
	public function onTwigSiteVariables(Event $event) {
		if (!$this->isAdmin()) {
			$pluginobject = (array) $this->config->get('plugins');
			$pluginobject = $pluginobject['directorylisting'];
			$pageobject = $this->grav['page'];
			$builtin_css = $pluginobject['builtin_css'];
			$builtin_js = $pluginobject['builtin_js'];
			$links = $pluginobject['links'];
			if (isset($pluginobject) && $pluginobject['enabled']) {
				$path = $pageobject->path();
				if ($builtin_css) {
					$this->grav['assets']->addCss('plugin://directorylisting/css/directorylisting.css');
				}
				if ($builtin_css && $builtin_js) {
					$this->grav['assets']->addJs('jquery');
					$this->grav['assets']->addJs('plugin://directorylisting/js/directorylisting.js');
				}
				$exclude_files = array();
				if ($pluginobject['exclude_main']) {
					$exclude_files[] = $pageobject->name();
				}
				if ($pluginobject['exclude_modular']) {
					if (isset($pageobject->header()->content['items'])) {
						if ($pageobject->header()->content['items'] == '@self.modular') {
							foreach ($pageobject->header()->content['order']['custom'] as $module) {
								$exclude_files[] = $module;
							}
						}
					}
				}
				if ($pluginobject['exclude_files']) {
					foreach ($pluginobject['exclude_files'] as $fileToExclude) {
						$exclude_files[] = $fileToExclude;
					}
				}
				$items = $this->dirToArray($path, $exclude_files);
				
				ob_start();
				echo '<div class="directorylist">';
				$this->recursiveArrayToList($items, $path, $links, $builtin_css);
				echo '</div>';
				$output = ob_get_contents();
				ob_end_clean();
				
				$this->grav['twig']->twig_vars['directorylisting'] = $output;
			}
		}
	}
}
