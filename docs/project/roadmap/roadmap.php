<?php
//error_reporting(E_NONE);

include ("jpgraph.php");
include ("jpgraph_gantt.php");
include ("textdb.php");

// Some global configs
// FIXME: read at least some of this from db/file so this file can be standalone for several projects.
if (!$title) {
  $title="Xaraya scenario roadmap";
}
$revision="2002-10-20";
$revtext="(Revision: $revision)";
$heightfactor=0.5;
$groupbarheight=0.1;
$groupbarcolor="black";
$groupbarmarker=MARK_DTRIANGLE;
$dependencylag="1"; // Allow 1 day for depency lag
$defaultfile="roadmap.txt";
$todaylabel="today";
$todaycolor="darkred";
// End global configs


// Standard calls to create a new graph
$graph = new GanttGraph(0,0,"auto");
$graph->SetShadow();
$graph->SetBox();

// Titles for chart
$graph->title->Set($title);
$graph->subtitle->Set($revtext);
$graph->title->SetFont(FF_FONT1,FS_BOLD,12);

// For illustration we enable all headers. 
$graph->ShowHeaders(GANTT_HYEAR | GANTT_HMONTH | GANTT_HDAY | GANTT_HWEEK);

// For the week we choose to show the start date of the week
// the default is to show week number (according to ISO 8601)
$graph->scale->week->SetStyle(WEEKSTYLE_FIRSTDAY);

// Change the scale font 
$graph->scale->week->SetFont(FF_FONT0);
$graph->scale->year->SetFont(FF_FONT1,FS_BOLD,12);

// Define a simple text format for tasks and planning to read in here,
// so chart can be created with several text files
if (!$file) $file=$defaultfile;
$db= new TextDb($file);
$record= $db->first();

// xaroad contains a list of records
// *id;label;start;duration;predecessor;progress;type;lead;part_of;
// start: date of start, if empty: today or if predecessor, from end of that one
// type: 0: grouping; 1: normal task; 2: milestone
// duration in days

// Algorithm for sophistication
// - DONE: scan database for group records and keep log of the latest date, so end-date can be set properly
// - DONE: keep track of predecessors and adjust the dates of successors
// - draw arrows from end of predecessor to begin of successors

// Generate the gantt bars
$plots=array();
$scenario=array();
$latestdate = array();

while($record) {
  switch ($record[type]) {
  case 0: // Grouping record 
    // params: line, label, start, end, caption, heightfactor 
    $bar = new GanttBar($db->recordNr,$record[label],$record[start],"",$record[lead],$groupbarheight);
    $bar->title->SetFont(FF_FONT1,FS_BOLD,8);
    $bar->SetColor($groupbarcolor);
    $bar->SetPattern(BAND_SOLID, $groupbarcolor);
    $bar->rightMark->Show();  $bar->rightMark->SetType($groupbarmarker);
    $bar->rightMark->SetFillColor($groupbarcolor);
    $bar->leftMark->Show();  $bar->leftMark->SetType($groupbarmarker);
    $bar->leftMark->SetFillColor($groupbarcolor);
    $scenario[$record[id]]=$bar;
    $plots[$record[id]]=$bar;
    break;
  case 1: // Normal task, indent
    // Calculate end date from start date and duration, if start-date is empty, use today
    if ($record[start]=="") $record[start]=date("Y-m-d");
    if ($record[duration]=="") $record[duration]=0;
    $enddate= date("Y-m-d",(strtotime($record[start])+($record[duration]*24*60*60)));
    if ($enddate > $latestdate[$record[part_of]] ) $latestdate[$record[part_of]]=$enddate;
    $bar = new GanttBar($db->recordNr," ".$record[label],$record[start],$enddate,"[".$record[progress]."%] ".$record[lead],$heightfactor);
    $bar->progress->Set($record[progress]/100);
    $plots[$record[id]]=$bar;
    break;
  case 2: // Milestone
    // pos, label, date, caption
    $ms = new MileStone($db->recordNr,$record[label],$record[start],$record[lead]);
    if ($record[start] > $latestdate[$record[part_of]]) $latestdate[$record[part_of]]=$record[start];
    $ms->title->Setfont(FF_FONT1,FS_BOLD,8);
    $plots[$record[id]]=$ms;
    break;
  }
  $record= $db->next();
}

// Now we have all plots in an array in memory and we can do some processing based on
// dependencies between the tasks
// $plots contains all plot objects
// 1. Adjust end dates for objects when they have a predecessor
// 2. Add lines from predecessor to successor and add them to the plot array
// 3. Adjust end date of grouping records so line will extend to whole project
$record = $db->first();
while($record) {
  // make the record end after its predecessor
  if ($record[predecessor]) {
    // Predecessor found, get enddate for that record and set 
    // begindate of current record at least to that date
    $searchrec=array('id' => $record[predecessor]);
    $pred = $db->search($searchrec);
    // Get the end date for the predecessor
    $earliest = $plots[$pred[id]]->GetMaxDate()+ $dependencylag*24*60*60;;
    
    // if no start date was give, plan after dependency
    if ($record[start]=='') {
      $plots[$record[id]]->iStart=$earliest;
      $plots[$record[id]]->iEnd=($earliest + $record[duration]*24*60*60);
    } else {
      // Set the end date at least equal to enddate of predecessor, add dependency lag
      $plots[$record[id]]->iEnd=max($earliest,$plots[$record[id]]->iEnd); 
    }
    
    // Adjust scenario dates if necessary
    if (date("Y-m-d",$plots[$record[id]]->iEnd) > $latestdate[$plots[$record[partof]]]) {
      $plots[$record[part_of]]->iEnd = $plots[$record[id]]->iEnd;
    }
  }
  $record=$db->next();
}


// Add things for which date doesn't change anymore to the graph here.
// Add a baseline for today
$vl = new GanttVLine(date("Y-m-d"),$todaylabel,$todaycolor);
$graph->Add($vl);

// Process the plot array for drawing 
while (list($key, $object) = each($plots)) {
  $graph->Add($object);
}

$graph->Stroke();

?>