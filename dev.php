<?php
//Set json headers
header('Content-Type: application/json');
// First we check if the cache exists and is younger than 30 mins
if (file_exists(base64_encode($_POST['username']). ".mango")) {
    if (time() - filemtime(base64_encode($_POST['username']). ".mango") > 1800) {
        //Continue
    } else {
        echo file_get_contents(base64_encode($_POST['username']). ".mango");
        exit(); // Terminate
    }
}
//Mink and other composer stuff
require_once 'vendor/autoload.php';

// Choose a Mink driver. More about it in later chapters.
$driver = new \Behat\Mink\Driver\GoutteDriver();

$session = new \Behat\Mink\Session($driver);

// start the session
$session->start();

// Now lets setup the Json

$jsonoutput = [
    "status" => "ok",
    "mangoCards" => [
        //Array-ception :p
        ]  
];



// Go to mango login page
$session->visit('https://trentbarton.co.uk/mango');
$page = $session->getPage();

// find username box
$usernameTxt = $page->find('named', array('id', 'Main_ctl00_ctl00_txtUsername'));
// find password box
$passwordTxt = $page->find('named', array('id', 'Main_ctl00_ctl00_txtPassword'));

//Find button 
$loginBtn = $page->find('named', array('id', 'Main_ctl00_ctl00_btnLogin'));

//Input details
$usernameTxt->setValue($_POST['username']);
$passwordTxt->setValue($_POST['password']);

// Time to click the button
$loginBtn->click();
// Now wait
sleep(5); //SECONDS!!
// Are we in yet?
$url = $session->getCurrentUrl();
if ($url != "https://trentbarton.co.uk/mango/welcome") {
    //Login failed
    $jsonoutput['status'] = "fail";
} else {
    // Now for the juicy stuff! Well, I guess mango's are juicy...
    $session->visit('https://www.trentbarton.co.uk/mango/my-mango'); //Lets go to the my mango page and say the credit of a mango
    sleep(5); //SECONDS!!
    //get the latest page var
    $page = $session->getPage();
    $mangoCards = $page->findAll('css', 'dl'); // A <dl> is a mango listing
    // List through the mangos
    foreach ($mangoCards as $mangoCard) {
        //Set up the mango Json
     $mangoArray = [
      'balance' => '',
      'cardholder' => [
          'name' => '',
          'nickname' => ''
          ],
    'trips' => [
          
    ],
      'type' => '',
      'number' => ''
    ];
    
    // Find all elements in the mango
    $mangoVars = $mangoCard->findAll('css', 'dd'); // A <dd> is a mango value
    /*
    NOTES:
    5 DD tags - Nick set
    4 DD Tags - No nick.
    
    Tag Index | Value
    ---------------------
    0         | Card Number
    1         | Card Type
    2         | Card Holder Full Name
    3         | Nickname(Friendly Name)*
    4         | Balance
    
    Balance is allways last index.
    
    Note if nickname is not set full name will be index 3.
    
    We can do a check for this by setting a varible that tells if the nick is set by counting the length:*/
    $nickIsSet = count($mangoVars) > 4;
    /*
    This should fix it.
    */
    
    //Now we just load the data in...
    
    
    $mangoArray['balance'] = $mangoVars[count($mangoVars)-1]->getText();
    
    if ($nickIsSet) {
        $mangoArray['cardholder']['name'] = $mangoVars[2]->getText();
        $mangoArray['cardholder']['nickname'] = $mangoVars[3]->getText();
    } else {
       $mangoArray['cardholder']['name'] = $mangoVars[3]->getText();  
    }
     $mangoArray['type'] = $mangoVars[1]->getText();
      $mangoArray['number'] = $mangoVars[0]->getText();
    // Now we can get the journey history. This requires the mango number so we just just take the -0 off the end split - 0
    $session->visit('https://www.trentbarton.co.uk/mango/my-mango/journey-history?card='. explode("-",$mangoArray['number'])[0]); 
    sleep(5); //5 seconds
    //Okay, now we can convert the HTML journey table to json array.
    /*
    Notes:
    The TB page uses some weird table in table thing, so there is a table of trips contatining mini tables.
    We shall proccess these by just doing for each table in the table then converting that.

    */



    $mangoTripData = $page->findAll('css', '.journey-history');
    $mangoTrips = $mangoTripData[0]->findAll('css', 'tr.times');
    $mangoPrices = $mangoTripData[0]->findAll('css', 'tr.credit');
foreach ($mangoTrips as $mangoTrip) {
$finMangoTrip = array_filter(array_map('trim',preg_split ('/\r\n|\n|\r/',$mangoTrip->getText())));
array_push($mangoArray['trips'], $finMangoTrip);
}
    //Okay, so now we add it to the json array
    array_push($jsonoutput['mangoCards'], $mangoArray);
    
    
    }
    
}

// End Mink Session MUST RUN!!
$session->reset();
$session->stop();

// Return JSON
echo json_encode($jsonoutput);
//Save to cache
file_put_contents(base64_encode($_POST['username']). ".mango", json_encode($jsonoutput));
?>
