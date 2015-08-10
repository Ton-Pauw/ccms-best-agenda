<?php
namespace agendaLedenNl;

/**
 * Agenda viewer for members section of ccms-best.nl
 *
 * @version 1.0.2
 * @author Ton van Lankveld
 * @license MIT
 */

/**
 * Loads a JSON file with a list of the meetings and there details
 *
 * @return array The content of the JSON file or empty if the file is not found
 */
function loadJSONfile() {
  $PATHDATAFILE = 'data/ccms-agenda.json';
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
 * Converts ISO8601 string to a readable Dutch date, like; Zondag 12 oktober
 *
 * @param string $iso860Str Date-time data in ISO8601 format (yyyy-mm-ddThh:mm:ss)
 * @return string If fault then empty, else HTML code like; <abbr title="Maandag 12 januari">Ma 12 jan</abbr>
 */
function iso8601ToHTMLdates($iso8601Str) {
  $MONTHS1 = ['januari', 'februari', 'maart', 'april', 'mei', 'juni', 'juli', 'augustus', 'september', 'oktober', 'november', 'december'];
  $MONTHS2 = ['jan', 'feb', 'mrt', 'apr', 'mei', 'jun', 'jul', 'aug', 'sep', 'okt', 'nov', 'dec'];
  $DAYOFTHEWEEK1 = ['Zondag', 'Maandag', 'Dinsdag', 'Woensdag', 'Donderdag', 'Vrijdag', 'Zaterdag'];
  $DAYOFTHEWEEK2 = ['Zo', 'Ma', 'Di', 'Wo', 'Do', 'Vr', 'Za'];
  $dayNumberInt = 0;
  $outStr = '';
  $dateObj = date_create_from_format('Y-m-d\TH:i:s', $iso8601Str);
  $dayNumberInt = $dateObj->format('w');
  // Build 2 date strings; day-of-week number month 
  $fullDateStr = $DAYOFTHEWEEK1[$dayNumberInt].' '.substr($iso8601Str, 8, 2).' '.$MONTHS1[((int) (substr($iso8601Str, 5, 2))-1)];
  $abbrDateStr = $DAYOFTHEWEEK2[$dayNumberInt].' '.substr($iso8601Str, 8, 2).' '.$MONTHS2[((int) (substr($iso8601Str, 5, 2))-1)];
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
  $timeStr = substr($meetingArray['start'], 11, 5).' - '.substr($meetingArray['end'], 11, 5).' uur';
  $contactStr = $meetingArray['contact'].' <a href="mailto:'.$meetingArray['email'].'\?subject=CCMS '.$meetingArray['groep'].'">E-mail</a>';
  // Build HTML string
  $outStr = '<tr><td>'.$dateStr.'</td><td>'.$timeStr.'</td><td>'.$meetingArray['onderwerp'].'</td><td>'.$meetingArray['groep'].'</td><td>'.$meetingArray['location'].'</td><td>'.$contactStr.'</td></tr>';
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
<html dir="ltr" lang="nl">
<head>
  <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
  <meta name="creator" content="Ton v. Lankveld">
  <meta name="description" content="Wat gebeurd er de komende tijd bij CCMS.">
  <title>CCMS-Leden - Agenda</title>
  <link rel="stylesheet" href="stijl/algemeen_screen.css" type="text/css" media="screen">
  <link rel="stylesheet" href="stijl/algemeen_print.css" type="text/css" media="print">
  <link rel="icon" href="stijl/favicon.png" type="image/png">
</head>
<body>
  <div id="header">
    <h1 lang="en">Computer Club Medical Systems</h1>
    <h2>Leden</h2>
  </div> <!-- einde van Header -->
  
  <div id="nav">
  <ul>
   <li><a href="nieuws_nl.html">Nieuws</a></li>
   <li id="activePage">Agenda</li>
   <li><a href="ledenactiviteiten_nl.html">Activiteiten</a></li>
   <li><a href="winkel_nl.html">Winkel</a></li>
   <li><a href="uitleen_nl.html">Uitleen</a></li>
   <li><a href="organisatie_nl.html">Organistatie</a></li>
   <li id="ccmsButton"><a href="http://www.ccms-best.nl/index.html">Uitloggen</a></li>
  </ul>
  </div> <!-- einde van nav -->
  
  <div id="content">
  <h3>Agenda</h3>
  
<table>
  <caption>Activiteiten in de komende periode</caption>
  <thead>
    <tr>
      <th scope="col">Datum</th>
      <th scope="col">Tijd</th>
      <th scope="col">Onderwerp</th>
      <th scope="col">Groep</th>
      <th scope="col">Locatie</th>
      <th scope="col">Contactpersoon</th>
    </tr>
  </thead>
  <tbody>
    <?php
      echo agendaViewer_Main();
    ?>
  </tbody>
</table>

  </div> <!-- einde van content-->
  
  <div id="footer">
    <p>De inhoud van deze site valt onder een <a rel="license" href="http://creativecommons.org/licenses/by-nc/3.0/nl/"><span lang="en">Creative Commons</span> licentie</a><span class="url"> (http://creativecommons.org/licenses/by-nc/3.0/nl/)</span>.</p>
  </div> <!-- einde van footer -->
  
</body>
</html>
