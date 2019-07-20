<?
/**
 * Parse the file at times.xml and output the list of restaurants, one per line.
 * Each line must show restaurant id, name and the times that it is open.
 * Example:
 *
 * #110: Harris Grill | Sun: 10am-2am; Mon-Sat: 11:30am-2am
 *
 * However, the challenge is that the hours of operation should be "compressed"
 * as much as possible - example:
 *
 * Sun-Tue, Thu-Sat: 11am-12am; Wed: 11pm-12am
 *
 * instead of:
 *
 * Sun: 11am-12am
 * Mon: 11am-12am
 * Tue: 11am-12am
 * Wed: 11pm-12am
 * Thu: 11am-12am
 * Fri: 11am-12am
 * Sat: 11am-12am
 *
 * Here is how the output for some of the entries in the file must look like:
 * #8385: La Palapa, Mexican Gourmet | Sun, Tue-Thu: 11am-9pm; Fri-Sat: 11am-11pm
 * #5201: Fat Head's Pittsburgh | Sun-Thu: 11am-11pm; Fri-Sat: 11am-12am
 * [..]
 * #111195: Rico's Restaurant | Mon-Thu: 11:30am-3pm & 3:30pm-10pm; Fri-Sat: 11:30am-3pm & 3:30pm-11pm
 * [..]
 * #17387: Z Pub & Diner | Sun, Tue-Thu: 8am-10pm; Mon: 4pm-10pm; Fri-Sat: 8am-12am
 *
 * You can use ANY libraries/helpers/frameworks that you want, as long as they would not
 * prevent me from running the code (meaning - if the file has external dependancies,
 * make sure to include them).
 * *

 */

/**
 * Class XMLParser
 * From a PHP.net comment on https://secure.php.net/manual/en/function.xml-parse-into-struct.php
 * Depends on the PHP-XML extension that's natively bundled with PHP.
 */
class XMLParser
{
    // raw xml
    private $rawXML;
    // xml parser
    private $parser = null;
    // array returned by the xml parser
    private $valueArray = array();
    private $keyArray = array();

    // arrays for dealing with duplicate keys
    private $duplicateKeys = array();

    // return data
    private $output = array();
    private $status;

    public function __construct($xml)
    {
        $this->rawXML = $xml;
        $this->parser = xml_parser_create();

        return $this->parse();
    }

    private function parse()
    {

        $parser = $this->parser;

        xml_parser_set_option($parser, XML_OPTION_CASE_FOLDING, 0); // Dont mess with my cAsE sEtTings
        xml_parser_set_option($parser, XML_OPTION_SKIP_WHITE, 1);     // Dont bother with empty info
        if (!xml_parse_into_struct($parser, $this->rawXML, $this->valueArray, $this->keyArray)) {
            $this->status = 'error: ' . xml_error_string(xml_get_error_code($parser)) . ' at line ' . xml_get_current_line_number($parser);

            return false;
        }
        xml_parser_free($parser);

        $this->findDuplicateKeys();

        // tmp array used for stacking
        $stack = array();
        $increment = 0;

        foreach ($this->valueArray as $val) {
            if ($val['type'] == "open") {
                //if array key is duplicate then send in increment
                if (array_key_exists($val['tag'], $this->duplicateKeys)) {
                    array_push($stack, $this->duplicateKeys[$val['tag']]);
                    $this->duplicateKeys[$val['tag']]++;
                } else {
                    // else send in tag
                    array_push($stack, $val['tag']);
                }
            } elseif ($val['type'] == "close") {
                array_pop($stack);
                // reset the increment if they tag does not exists in the stack
                if (array_key_exists($val['tag'], $stack)) {
                    $this->duplicateKeys[$val['tag']] = 0;
                }
            } elseif ($val['type'] == "complete") {
                //if array key is duplicate then send in increment
                if (array_key_exists($val['tag'], $this->duplicateKeys)) {
                    array_push($stack, $this->duplicateKeys[$val['tag']]);
                    $this->duplicateKeys[$val['tag']]++;
                } else {
                    // else send in tag
                    array_push($stack, $val['tag']);
                }

                $this->setArrayValue($this->output, $stack, array_key_exists('value', $val) ? $val['value'] : "");
                array_pop($stack);
            }
            $increment++;
        }

        $this->status = 'success: xml was parsed';

        return true;

    }

    private function findDuplicateKeys()
    {
        for ($i = 0; $i < count($this->valueArray); $i++) {
            // duplicate keys are when two complete tags are side by side
            if ($this->valueArray[$i]['type'] == "complete") {
                if ($i + 1 < count($this->valueArray)) {
                    if ($this->valueArray[$i + 1]['tag'] == $this->valueArray[$i]['tag'] && $this->valueArray[$i + 1]['type'] == "complete") {
                        $this->duplicateKeys[$this->valueArray[$i]['tag']] = 0;
                    }
                }
            }
            // also when a close tag is before an open tag and the tags are the same
            if ($this->valueArray[$i]['type'] == "close") {
                if ($i + 1 < count($this->valueArray)) {
                    if ($this->valueArray[$i + 1]['type'] == "open" && $this->valueArray[$i + 1]['tag'] == $this->valueArray[$i]['tag']) {
                        $this->duplicateKeys[$this->valueArray[$i]['tag']] = 0;
                    }
                }
            }
        }
    }

    private function setArrayValue(&$array, $stack, $value)
    {
        if ($stack) {
            $key = array_shift($stack);
            $this->setArrayValue($array[$key], $stack, $value);

            return $array;
        } else {
            $array = $value;
        }
    }

    public function getOutput()
    {
        return $this->output;
    }
}

$xmlFile = file_get_contents("times.xml");
$parser = new XMLParser($xmlFile);
$restaurants = $parser->getOutput();


$output = '';





function formatDaysAndHours(array $daysAndTimes, $days =[
0 => 'Sun',
1 => "Mon",
2 => "Tue",
3 => "Wed",
4 => "Thu",
5 => "Fri",
6 => "Sat",
]) {

    //gets all the unique times ranges;
    $uniqueTimes = array_unique($daysAndTimes);

    $string = '';
    $combinedDaysAndTimes = [];
    //find all the days that have this time schedule
    foreach($uniqueTimes as $hours) {
        $daysWithTheseHours = array_keys($daysAndTimes, $hours);
        sort($daysWithTheseHours);  
        $processedDays = 0;
        
        $combinedDays =[];
        // BUild the days string
        foreach($daysWithTheseHours as $key =>  $day) {
            
            //check for consectutive days
            $tomorrow = $day +1;;
            if(in_array($tomorrow, $daysWithTheseHours)) {
                $consectiveDays = 0;
                while (in_array($tomorrow, $daysWithTheseHours)) {
                   $consectiveDays = $consectiveDays + 1;
                   $tomorrow = $tomorrow + 1;
                }
                 $combinedDays[] = $days[$day] .'-'. $days[$day+$consectiveDays] ;
                 $processedDays = $processedDays + $consectiveDays + 1 ;
            }  else {
                $processedDays = $processedDays + 1;
                $combinedDays[] = $days[$day] ;
            } 

            //check to see if the list of days with this time is in the result

            if($processedDays == count($daysWithTheseHours)) {
               break ;
            }
        } 
        

        $combinedDaysAndTimes[] = implode($combinedDays, ', ') . ': ' . $hours ;
      

    }

    //Put Sundays First
    foreach ($combinedDaysAndTimes as $key => $hoursGroups) {
        if(strpos($hoursGroups, 'Sun') === 0) {
           unset($combinedDaysAndTimes[$key]);
           array_unshift($combinedDaysAndTimes, $hoursGroups);
           break;
        }

    } 
     //Put Satursays Last
    foreach ($combinedDaysAndTimes as $key => $hoursGroups) {
        if(strpos($hoursGroups, 'Sat') === 0) {
           unset($combinedDaysAndTimes[$key]);
           $combinedDaysAndTimes[] = $hoursGroups;
           break;
        }
    }        

 return implode($combinedDaysAndTimes, '; ');


}





foreach ($restaurants['response']['data'] as $key => $restaurant) {
    


    $daysAndTimes = [];
    
    foreach($restaurant['schedule'] as $period) {
       
        if($period['open']['day'] != $period['close']['day']) {

           $open = $period['open']['time']; 
           $close = $period['close']['time']; 
           $hours = date('g:ia', strtotime($open)) .'-'.date('g:ia', strtotime($close));
            $hours = str_replace(':00', '', $hours);
         $daysAndTimes[$period['open']['day']] =   $hours;
         
        } else {
           $open = $period['open']['time']; 
           $close = $period['close']['time']; 
           $hours = date('g:ia', strtotime($open)) .'-'.date('g:ia', strtotime($close));
           $hours = str_replace(':00', '', $hours);
           if(empty($daysAndTimes[$period['open']['day']])) {
            $daysAndTimes[$period['open']['day']] =   $hours;
           } else {
            $daysAndTimes[$period['open']['day']] =  $daysAndTimes[$period['open']['day']] . ' & ' . $hours;
           }
        }
    }



    $line = '#'.$restaurant['biz_id']. ': ' .trim($restaurant['biz_name']) . ' | ' . formatDaysAndHours($daysAndTimes);

    
    //#111195: Rico's Restaurant | Mon-Thu: 11:30am-3pm & 3:30pm-10pm; Fri-Sat: 11:30am-3pm & 3:30pm-11pm
    $output .=  $line . "\n";



}
echo $output;
