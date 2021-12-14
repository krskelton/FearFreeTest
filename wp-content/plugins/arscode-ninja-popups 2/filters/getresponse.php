<?php
	
add_filter('ninja_popups_subscribe_by_getresponse', 'ninja_popups_subscribe_by_getresponse', 10, 1);

function ninja_popups_subscribe_by_getresponse($params = array()) 
{
    require_once SNP_DIR_PATH . '/include/httpful.phar';
	
	$ml_gr_apikey = snp_get_option('ml_gr_apikey');

	try {
		$ml_gr_list = $params['popup_meta']['snp_ml_gr_list'][0];
		if (!$ml_gr_list) {
			$ml_gr_list = snp_get_option('ml_gr_list');
		}
		
        $result = array(
			'status' => false,
			'log' => array(
				'listId' => $ml_gr_list,
				'errorMessage' => '',
			)
		);

        $body = [
			'campaign' => [
			    'campaignId' => $ml_gr_list
            ],
			'email' => snp_trim($params['data']['post']['email']),
			'dayOfCycle' => '0',
        ];
        
        if (!empty($params['data']['post']['name'])) {
            $body['name'] = $params['data']['post']['name'];
	    }
	    
	    if (count($params['data']['cf']) > 0) {
		    $CustomFields = [];
		    foreach ($params['data']['cf'] as $k => $v) {
			    $CustomFields[] = [
			    	'customFieldId' => $k,
					'value' => [
					    $v
                    ]
				];
			}

            $body['customFieldValues'] = $CustomFields;
        }

	    //print_r($body);

        $response = \Httpful\Request::post('https://api.getresponse.com/v3/contacts')
            ->sendsJson()
            ->addHeader('X-Auth-Token',  'api-key ' . $ml_gr_apikey)
            ->expectsJson()
            ->body($body)
            ->send();

        $result['status'] = true;               
    } catch (Exception $e) {
	    $result['log']['errorMessage'] = $e->getMessage();
    }
	
	return $result;
}