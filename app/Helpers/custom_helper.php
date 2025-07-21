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



if (!function_exists('display_errors')) {
    function display_errors($errors) {
        if ($errors->any()) {
            $output = '<div class="alert alert-danger alert-dismissible fade show" role="alert">';
            foreach ($errors->all() as $error) {
                $output .= $error . '<br>';
            }
            $output .= '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>';
            $output .= '</div>';
            return $output;
        }
    }
}

if (!function_exists('display_message')) {
    function display_message($message) {
        $output = '<div class="alert alert-success alert-dismissible fade show" role="alert">
                '.$message.'<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>';   
          return $output;
    }
}
?>