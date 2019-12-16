<?php
/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 *
 */
namespace Piwik\Plugins\SharpSpringWidgetByAmperage\Widgets;

use Piwik\Widget\Widget;
use Piwik\Widget\WidgetConfig;
use Piwik\View;

/**
 * This class allows you to add your own widget to the Piwik platform. In case you want to remove widgets from another
 * plugin please have a look at the "configureWidgetsList()" method.
 * To configure a widget simply call the corresponding methods as described in the API-Reference:
 * http://developer.piwik.org/api-reference/Piwik/Plugin\Widget
 */
class GetSharpSpringInfo extends Widget{
    public static function configure(WidgetConfig $config){
        /**
         * Set the category the widget belongs to. You can reuse any existing widget category or define
         * your own category.
         */
        $config->setCategoryId('SharpSpringWidgetByAmperage_SharpSpring');

        /**
         * Set the subcategory the widget belongs to. If a subcategory is set, the widget will be shown in the UI.
         */
        // $config->setSubcategoryId('General_Overview');

        /**
         * Set the name of the widget belongs to.
         */
        $config->setName('SharpSpringWidgetByAmperage_SharpSpringLeads');

        /**
         * Set the order of the widget. The lower the number, the earlier the widget will be listed within a category.
         */
        $config->setOrder(50);

        /**
         * Optionally set URL parameters that will be used when this widget is requested.
         * $config->setParameters(array('myparam' => 'myvalue'));
         */

        /**
         * Define whether a widget is enabled or not. For instance some widgets might not be available to every user or
         * might depend on a setting (such as Ecommerce) of a site. In such a case you can perform any checks and then
         * set `true` or `false`. If your widget is only available to users having super user access you can do the
         * following:
         *
         * $config->setIsEnabled(\Piwik\Piwik::hasUserSuperUserAccess());
         * or
         * if (!\Piwik\Piwik::hasUserSuperUserAccess())
         *     $config->disable();
         */
    }

    public function amp_get_contents($url){
		if(ini_get('allow_url_fopen')){
			return file_get_contents($url);
		}else{
			$curl = curl_init();
			curl_setopt($curl, CURLOPT_URL, $url);
			curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($curl, CURLOPT_HEADER, false);
			$output = curl_exec($curl);
			curl_close($curl);
			return $output;
		}
    }

    /**
     * This method renders the widget. It's on you how to generate the content of the widget.
     * As long as you return a string everything is fine. You can use for instance a "Piwik\View" to render a
     * twig template. In such a case don't forget to create a twig template (eg. myViewTemplate.twig) in the
     * "templates" directory of your plugin.
     *
     * @return string
     */
    public function render(){
        try {

			$debug_help = false; // Toggle whether the SharpSpring API helper text should be shown
			$debug_leads = false; // Toggle whether the SharpSpring Lead raw output should be shown
			$debug_leads_details = false; // Toggle whether the SharpSpring Lead output (parsed & roughly formatted) should be shown

	        $output = '<div class="widget-body">';

			$api_key = '';
			$secret = '';

			// Get Piwik user's settings for the API Key & Secret they've set there (allowing different Piwik users to have different SharpSpring authentication)
			$settings = new \Piwik\Plugins\SharpSpringWidgetByAmperage\UserSettings();
			$api_key = $settings->sharpSpringAPIKey->getValue();
			$secret = $settings->sharpSpringSecretKey->getValue();

			if($api_key == '' || $secret == ''){
				$output.= '<p>You first need to configure the SharpSpring API Keys in your <a href="index.php?module=UsersManager&action=userSettings#SharpSpringWidgetByAmperage">user settings</a>.</p>';
			}else{ // API Keys have been provided

		        if($debug_help){
			        $output.= '<p>This is where the SharpSpring API data would be shown (leads, campaigns, etc. as documented at <a href="https://help.sharpspring.com/hc/en-us/articles/115001069228-Open-API-Overview" target="_blank">https://help.sharpspring.com/hc/en-us/articles/115001069228-Open-API-Overview</a> and <a href="https://marketingautomation.services/settings/pubapireference" target="_blank">https://marketingautomation.services/settings/pubapireference</a> [exact URL may be different after logging in])</p>';
		        }

				$method = 'getLeads';
				$params = array('where' => array(), 'limit' => 5, 'offset' => 0);
				$requestID = session_id();
				$data = array(
					'method' => $method,
					'params' => $params,
					'id' => $requestID,
				);
				$data = json_encode($data);
				$ch = curl_init('https://api.sharpspring.com/pubapi/v1/?accountID='.$api_key.'&secretKey='.$secret);
				curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
				curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
				curl_setopt($ch, CURLOPT_HTTPHEADER, array(
					'Content-Type: application/json',
					'Content-Length: ' . strlen($data)
				));
				$result = curl_exec($ch);
				curl_close($ch);

		        if($debug_leads){
			        $output.= '<p><strong>Example lead retrieval:</strong> <code>'.$result.'</code></p>';
		        }

				if($debug_leads_details){
			        $leads = json_decode($result);
					$leadCount = 0;
					foreach($leads->result as $lead){
						foreach($lead as $lead_details){
							$output.= '<dl class="sharpspring-lead">';
							foreach($lead_details as $key => $value){
								$output.= '<dt>'.$key.'</dt><dd>'.$value.'</dd>';
							}
							$output.='</dl><!-- .sharpspring-lead -->';
						}
						$leadCount++;
					}
				}

		        $leads = json_decode($result);
				$leadCount = 0;
				foreach($leads->result as $lead){
					foreach($lead as $lead_details){
						$output.= '<a href="https://marketingautomation.services/lead/'.$lead_details->id.'" target="_blank" class="sharpspring-lead alert alert-info">';
						$output.= '<strong>Name</strong>';
						$output.= '<span>'.$lead_details->firstName.' '.$lead_details->lastName.'</span>';
						$output.= '<strong>Company Name</strong>';
						$output.= '<span>'.$lead_details->companyName.'</span>';
						$output.= '<strong>Title</strong>';
						$output.= '<span>'.$lead_details->title.'</span>';
						$output.= '<strong>Lead Score</strong>';
						$output.= '<span>'.$lead_details->leadScoreWeighted.'</span>';
						$output.= '<strong>Lead Status</strong>';
						$output.= '<span>'.ucfirst($lead_details->leadStatus).'</span>';
						$output.= '<strong>Updated Timestamp</strong>';
						$output.= '<span>'.$lead_details->updateTimestamp.'</span>';
						$output.='</a><!-- .sharpspring-lead -->';
					}
					$leadCount++;
				}

				if($leadCount<1){
					$output.= '<p>No <a href="https://marketingautomation.services/" target="_blank">SharpSpring</a> leads have been recorded using the account the SharpSpring API keys are for yet.</p>';
				}

	        }

	        $output.= '</div>';
	        return $output;

        } catch (\Exception $e) {
            return $this->error($e);
        }
    }

    /**
     * @param \Exception $e
     * @return string
     */
    private function error($e)
    {
        return '<div class="pk-emptyDataTable">'
             . Piwik::translate('General_ErrorRequest', array('', ''))
             . ' - ' . $e->getMessage() . '</div>';
    }

}