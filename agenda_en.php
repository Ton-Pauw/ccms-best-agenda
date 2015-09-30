<?php
namespace agendaPubliekEn;

/**
 * Agenda viewer for English public section of www.ccms-best.nl
 *
 * @version 1.0.0
 * @author Ton van Lankveld
 * @license MIT
 */

/**
 * Loads a JSON file with a list of the meetings and there details
 *
 * @return array The content of the JSON file or empty if the file is not found
 */
function loadJSONfile() {
  $PATHDATAFILE = 'path/to/agenda.json';
  $outArray = [];
  if (file_exists($PATHDATAFILE)) {
    $handle = fopen($PATHDATAFILE, "r");
    $fileData = fread($handle, filesize($PATHDATAFILE));
    fclose($handle);
  } else { return $outArray; }
  $outArray = json_decode($fileData, true);
  return $outArray;
}

/**
 * Filter a string and allow only characters in the white list string
 *
 * @param string $inpStr
 * @param string $whiteListStr Allowed characters
 * @return string If fault then empty
 */
 function whiteFilterStr($inpStr, $whiteListStr) {
  $strClean = '';
  
  if (!is_string($inpStr) or !is_string($whiteListStr)) {
     return $strClean;
    }
  $inpStrLen = strlen($inpStr);
  $WLstrLen = strlen($whiteListStr);
  # Filter the input string with the whitelist
  $i = 0;
  while ($i < $inpStrLen) {
    if (strpos($whiteListStr, $inpStr{$i}, 0) !== false) {
      $strClean = $strClean.$inpStr{$i};
    }
  $i++;
  }
  return $strClean;
}

/**
 * Filter and validate a string with local date-time data in ISO8601 format
 *
 * @param string $inpStr 
 * @return string If fault then empty, else correct ISO8601 string
 */
function filterValidateIso8601($inpStr) {
  $WHITELIST = '0123456789-:T';
  # source of pattern is https://stackoverflow.com/questions/8003446/php-validate-iso-8601-date-string
  $ISO8601PATTERN = '/^(\d{4})-(\d{2})-(\d{2})T(\d{2}):(\d{2}):(\d{2})$/'; # Format: yyyy-mm-ddThh:mm:ss
  $strClean = '';
  $inpStrLen = strlen($inpStr);
  $WLstrLen = strlen($WHITELIST);
  if (!is_string($inpStr)) {
    return $strClean;
    }
  
  $strClean = whiteFilterStr($inpStr, $WHITELIST);
  
  if (!preg_match($ISO8601PATTERN, $strClean)) {
    $strClean = '';
  }
  return $strClean;
} 

/**
 * Filter and validate the strings of a meeting
 *
 * @param array $meetingArray_input The meeting parameters with input strings of: start, end, onderwerp, subject, groep, group, location, contact and email
 * @return array The clean strings
 */
function filterValidateMeetingArray($inpArray) {
  $meetingArrayClean = [
    'start'     => '',
    'end'       => '',
    'onderwerp' => '',
    'subject'   => '',
    'groep'     => '',
    'group'     => '',
    'location'  => '',
    'contact'   => '',
    'email'     => ''
  ];
  $startClean = filterValidateIso8601($inpArray['start']);
  if ($startClean) {
    $meetingArrayClean['start'] = $startClean;
  };
  $endClean = filterValidateIso8601($inpArray['end']);
  if ($endClean) {
    $meetingArrayClean['end'] = $endClean;
  };
  if (is_string($inpArray['onderwerp'])) {
    $onderwerpClean = htmlspecialchars($inpArray['onderwerp'], ENT_HTML401);
    if ($onderwerpClean) {
      $meetingArrayClean['onderwerp'] = $onderwerpClean;
    };
  };
  if (is_string($inpArray['subject'])) {
    $subjectClean = htmlspecialchars($inpArray['subject'], ENT_HTML401);
    if ($subjectClean) {
      $meetingArrayClean['subject'] = $subjectClean;
    };
  };
  if (is_string($inpArray['groep'])) {
    $groepClean = strip_tags($inpArray['groep']);
    if ($groepClean) {
      $meetingArrayClean['groep'] = $groepClean;
    };
  };
  if (is_string($inpArray['group'])) {
    $groupClean = strip_tags($inpArray['group']);
    if ($groupClean) {
      $meetingArrayClean['group'] = $groupClean;
    };
  };
  if (is_string($inpArray['location'])) {
    $locationClean = strip_tags($inpArray['location']);
    if ($locationClean) {
      $meetingArrayClean['location'] = $locationClean;
    };
  };
  if (is_string($inpArray['contact'])) {
    $contactClean = strip_tags($inpArray['contact']);
    if ($contactClean) {
      $meetingArrayClean['contact'] = $contactClean;
    };
  };
  if (is_string($inpArray['email'])) {
    $emailSanitized = filter_var($inpArray['email'], FILTER_SANITIZE_EMAIL);
    if (filter_var($emailSanitized, FILTER_VALIDATE_EMAIL)) {
      $meetingArrayClean['email'] = $emailSanitized;
    };
  };
  return $meetingArrayClean;
}

/**
 * Converts ISO8601 string to a readable date, like; Monday January 12
 *
 * @param string $iso860Str Date-time data in ISO8601 format (yyyy-mm-ddThh:mm:ss)
 * @return string If fault then empty, else HTML code like; <abbr title="Monday January 12">Mon Jan 12</abbr>
 */
function iso8601ToHTMLdates($iso8601Str) {
  $outStr = '';
  $dateObj = date_create_from_format('Y-m-d\TH:i:s', $iso8601Str);
  // Build 2 date strings; day-of-week number month
  $fullDateStr = $dateObj->format("l F j");
  $abbrDateStr = $dateObj->format("D M j");
  $outStr = '<abbr title="'.$fullDateStr.'">'.$abbrDateStr.'</abbr>'; // Build HTML string
  return $outStr;
}

/**
 * Place all data from one meeting within HTML <tr> and <td> tags.
 *
 * @param array $meetingArray All data of one meeting.
 * @return string If fault then empty, else one table row in HTML code.
 */
function HTMLtableRow($meetingArray) {
  $outStr = "";

  $dateStr = iso8601ToHTMLdates($meetingArray['start']);
  $timeStr = substr($meetingArray['start'], 11, 5).' - '.substr($meetingArray['end'], 11, 5).' hour';
  // Build HTML string
  $outStr = '<tr><td>'.$dateStr.'</td><td>'.$timeStr.'</td><td>'.$meetingArray['subject'].'</td><td>'.$meetingArray['group'].'</td></tr>';
  return $outStr;
}

/**
 * Main loop of the Agenda Viewer
 *
 * @return string If fault then empty, else table rows with meeting details
 */
function agendaViewer_Main() {
  $inputMeetingsArray = loadJSONfile();
  $arrayLength = count($inputMeetingsArray);
  $nowDateObj = date_create("now");
  $oneMeetingArray = [];
  $HTMLstr = "";
  $i = 0;
  
  while ($i < $arrayLength) {
    $oneMeetingArray = filterValidateMeetingArray($inputMeetingsArray[$i]);
    $endDateObj = date_create_from_format('Y-m-d\TH:i:s', $oneMeetingArray['end']);
    if ($endDateObj > $nowDateObj) {
      $HTMLstr = $HTMLstr . HTMLtableRow($oneMeetingArray);
    }
    $i++;
  }
  return $HTMLstr;
}

?>

<!DOCTYPE HTML PUBLIC
  "-//W3C//DTD HTML 4.01//EN"
  "http://www.w3.org/TR/html4/strict.dtd">
<html dir="ltr" lang="en-US">
<head>
  <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
  <meta name="creator" content="Ton v. Lankveld">
  <meta name="description" content="Upcoming events of Computer Club Medical Systems.">
  <title>CCMS - Agenda</title>
  <link rel="stylesheet" href="stijl/algemeen_screen.css" type="text/css" media="screen">
  <link rel="stylesheet" href="stijl/algemeen_print.css" type="text/css" media="print">
  <link rel="icon" href="stijl/favicon.png" type="image/png">
</head>
<body>
  <div id="header">
    <h1>Computer Club Medical Systems</h1>
    <h2>Public</h2>
  </div> <!-- end of Header -->
  
  <div id="nav">
  <ul>
   <li><a href="index_en.html" title="Introduction">Intro</a></li>
	<li id="activePage">Agenda</li>
	<li><a href="leden_en.html" title="Log-in screen">Members</a></li>
	<li><a href="word_lid_en.html" title="Become a CCMS-member">Join us!</a></li>
	<li><a href="activiteiten_en.html" title="Activities which we organize">Activities</a></li>
	<li><a href="over_ons_en.html" title="Background information">About us</a></li>
	<li><a href="contact_en.html" title="Contact information">Contact</a></li>
	<li id="languages" lang="nl"><a href="agenda_nl.php" title="Nederlandse versie"><img src="stijl/vlag_nl.png" width="45px" height="30px" alt="Nederlandse versie" title="Nederlandse versie"></a></li>
  </ul>
  </div> <!-- end of nav -->
  
  <div id="content">
  <h3>Agenda</h3>
  <p>These activities are only for members and guests of Computer Club Medical Systems. Or otherwise specified.</p>
  
<table>
  <caption>Upcoming Events</caption>
  <thead>
    <tr>
      <th scope="col">Date</th>
      <th scope="col">Time</th>
      <th scope="col">Subject</th>
      <th scope="col">Group</th>
    </tr>
  </thead>
  <tbody>
    <?php
      echo agendaViewer_Main();
    ?>
  </tbody>
</table>

  </div> <!-- end of the content -->
  
  <div id="footer">
    <p>Content on this site is licensed under a <a rel="license" href="https://creativecommons.org/licenses/by-nc/3.0/">Creative Commons License</a><span class="url"> (https://creativecommons.org/licenses/by-nc/3.0/)</span>.</p>
  </div> <!-- end of footer -->
  
</body>
</html>
