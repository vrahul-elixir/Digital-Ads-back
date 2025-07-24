<?php
if (! function_exists('pre')) {
    function pre($data)
    {
        echo '<pre>';
        print_r($data);
        echo '</pre>';
    }
}

function isPastDate($fetchDate)
{   
    if(empty($fetchDate) || is_null($fetchDate))
    {
        return true;
    }

    try {
        $date = new \DateTime($fetchDate);
        $today = new \DateTime();
        return $date->format('Y-m-d') < $today->format('Y-m-d');
    } catch (\Exception $e) {
        // Optional: handle invalid date format
        return false;
    }
}
if (! function_exists('changeDate')) {
    function changeDate($date, $format = 'Y-m-d')
    { 
       $newDate = date($format, strtotime($date));
       return $newDate;
    }
}

if (! function_exists('RemoveSpecialWord')) {
    function RemoveSpecialWord($string) {

        $garbagearray = array('@','#','$','%','^','&','*');
        $garbagecount = count($garbagearray);
        for ($i=0; $i<$garbagecount; $i++) {
            $string = str_replace($garbagearray[$i], '', $string);
        }
        
        return $string;
    }
        
}

if (!function_exists('getCurrentDateTimeIndia')) {
    function getCurrentDateTimeIndia($format = 'Y-m-d H:i:s') // Default format: Y-m-d H:i:s
    {
        date_default_timezone_set('Asia/Kolkata'); // Set timezone to Indian Standard Time
        $currentDateTime = date($format);
        return $currentDateTime;
    }
}
if (!function_exists('removeSpecialCharacters')) {
    function removeSpecialCharacters($string) {
        // Use a regular expression to remove all non-alphanumeric characters
        $result = str_replace('"', "'", $string);
    
        // Return the cleaned string
        return $result;
    }
}

if (!function_exists('addDaysToCurrentDate')) {
    function addDaysToCurrentDate($duration, $price_base)
    {
        date_default_timezone_set('Asia/Kolkata');
        $currentDate = new DateTime();

        if ($price_base == 1) {
            // Monthly: add duration * 30 days
            $daysToAdd = $duration * 30;
        } elseif ($price_base == 2) {
            // Yearly: add duration * 365 days
            $daysToAdd = $duration * 365;
        } else {
            // Default: no addition
            $daysToAdd = 0;
        }

        $currentDate->modify("+{$daysToAdd} days");
        return $currentDate->format('Y-m-d H:i:s');
    }
}
?>