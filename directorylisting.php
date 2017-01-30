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
			$exclude[] = $exclude_extra;
		}
		foreach ($cdir as $key => $value) {
			if (!in_array($value, $exclude)) {
				if (is_dir($dir . DIRECTORY_SEPARATOR . $value)) {
					$result[$value] = $this->dirToArray($dir . DIRECTORY_SEPARATOR . $value);
				} else {
					$result[] = $dir . DIRECTORY_SEPARATOR . $value;
				}
			}
		}
		return $result;
	}
	
	/* Source: http://stackoverflow.com/a/36334789/603387 */
	private function recursiveArrayToList($array, $links = false, $builtin_css = false) {
		echo '<ul class="directorylisting">';
		foreach ($array as $key => $value) {
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
				$this->recursiveArrayToList($value, $links, $builtin_css);
			} else {
				$parts = pathinfo($value);
				if ($links) {
					echo '<li class="item file"><a href="' . $value . '">' . $parts['filename'] . '.' . $parts['extension'] . '</a></li>';
				} else {
					echo '<li class="item file">' . $parts['filename'] . '.' . $parts['extension'] . '</li>';
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
				if ($pluginobject['exclude_main']) {
					$current_file = $pageobject->name();
					$items = $this->dirToArray($path, $current_file);
				} else {
					$items = $this->dirToArray($path);
				}
				
				ob_start();
				echo '<div class="directorylist">';
				$this->recursiveArrayToList($items, $links, $builtin_css);
				echo '</div>';
				$output = ob_get_contents();
				ob_end_clean();
				
				$this->grav['twig']->twig_vars['directorylisting'] = $output;
			}
		}
	}
}