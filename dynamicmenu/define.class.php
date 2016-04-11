<?php
/**
 * Dynamic menu profile field definition.
 *
 * @package    profilefield_dynamicmenu
 * @copyright  2016 onwards Antonello Moro {@link http://treagles.it}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

class profile_define_dynamicmenu extends profile_define_base {

    /**
     * Adds elements to the form for creating/editing this type of profile field.
     * @param moodleform $form
     */
    public function define_form_specific($form) {
    	
        // Param 1 for menu type contains the options.
        $form->addElement('textarea', 'param1', get_string('sqlquery', 'profilefield_dynamicmenu'), array('rows' => 6, 'cols' => 40));
        $form->setType('param1', PARAM_TEXT);
        $form->addHelpButton('param1', 'param1sqlhelp', 'profilefield_dynamicmenu');
        // Default data.
        $form->addElement('text', 'defaultdata', get_string('profiledefaultdata', 'admin'), 'size="50"');
        $form->setType('defaultdata', PARAM_TEXT);
        
        //let's see if the user can modify the sql
        global $USER;
        $context = context_system::instance();
        $hascap=has_capability('profilefield/dynamicmenu:caneditsql', $context);
        
        if (!$hascap){
        	$form->hardFreeze('param1');
        	$form->hardFreeze('defaultdata');
        }
        $form->addElement('text', 'sql_count_data',get_string('numbersqlvalues', 'profilefield_dynamicmenu') );
        $form->setType('sql_count_data', PARAM_RAW);
        $form->hardFreeze('sql_count_data');
        $form->addElement('textarea', 'sql_sample_data',get_string('samplesqlvalues', 'profilefield_dynamicmenu') , array('rows' => 6, 'cols' => 40));
        $form->setType('sql_sample_data', PARAM_RAW);
        $form->hardFreeze('sql_sample_data');
    }

    /**
     * Alter form based on submitted or existing data
     * @param moodleform $mform
     */
    public function define_after_data(&$form) {
        global $DB;
        try{
        	$sql = $form->getElementValue('param1');
        	
        	if ($sql){
        		$rs=$DB->get_records_sql($sql);
        		$i=0;
        		$def_sample='';
        		$count_data=count($rs);
        		foreach ($rs as $record){
        			if ($i==12){
        				exit;
        			}
        			if (isset($record->data) && isset($record->id)){
        				if (strlen($record->data)>40){
        					$sampleval=substr($record->data,0,36).'...';
        				}else{
        					$sampleval=$record->data;
        				}
        				$def_sample.='id: '.$record->id .' - data: '.$sampleval."\n";
        			}
        		}
        		$form->setDefault('sql_count_data', $count_data);
        		$form->setDefault('sql_sample_data', $def_sample);
        	}
        }catch (Exception $e) {
        	//Do nothing. Errors at this pahse are handled in define_validate_specific
        }

    }
    /**
     * Validates data for the profile field.
     *
     * @param array $data
     * @param array $files
     * @return array
     */
    public function define_validate_specific($data, $files) {
        $err = array();

        $data->param1 = str_replace("\r", '', $data->param1);
        //provo ad eseguire la query
        $sql=$data->param1;
        global $DB;
        try{
        	$rs=$DB->get_records_sql($sql);
        	if (!$rs){
        		$err['param1']=get_string('queryerrorfalse', 'profilefield_dynamicmenu');
        	}else{
        		if (count($rs)==0){
        			$err['param1']=get_string('queryerrorempty', 'profilefield_dynamicmenu');
        		}else{
        			$firstval=reset($rs);
        			if (!object_property_exists($firstval,'id')){
        				$err['param1']=get_string('queryerroridmissing', 'profilefield_dynamicmenu');
        			}else{
        				if (!object_property_exists($firstval,'data')){
        					$err['param1']=get_string('queryerrordatamissing', 'profilefield_dynamicmenu');
        				}elseif(!empty($data->defaultdata) && !isset($rs[$data->defaultdata])){
        					//def missing
        					$err['defaultdata'] = get_string('queryerrordefaultmissing', 'profilefield_dynamicmenu');
        				}
        			}
        		}
        	}
        }catch (Exception $e) {
        	$err['param1']=get_string('sqlerror','profilefield_dynamicmenu') . ': ' .$e->getMessage();
        }
        
        return $err;
    }

    /**
     * Processes data before it is saved.
     * @param array|stdClass $data
     * @return array|stdClass
     */
    public function define_save_preprocess($data) {
        $data->param1 = str_replace("\r", '', $data->param1);

        return $data;
    }

}


