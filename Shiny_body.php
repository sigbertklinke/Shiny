<?php

class Shiny {

  static function hashmethod() {
    $arr = array_intersect(array('tiger160,3', // 40 length of result
	          	         'tiger192,3', // 48
		           	 'ripemd256',  // 64
		          	 'ripemd320',  // 80 
		          	 'sha384',     // 96
		          	 'sha512',     // 128
		          	 'sha1'),      // 40 the fallback(!)
		            hash_algos());
    return ($arr[0]);
  }

  static function setHooks ($parser) {
    global $wgHooks;
    $parser->setHook( 'shiny' , 'Shiny::renderTagShiny' );
    return true; 
  }

  static function renderTagShiny ($input, $argv, $parser, $frame) {
    global $wgShinyAppDir, $wgShinyUrl, $wgSitename; 
    $err     = false;
    $hashmtd = Shiny::hashmethod();
// check iframe options
    $iframeopt = array('height'   => array('value' => 600,                    'pattern' => '/^[0-9]+$/'),
                       'width'    => array('value' => 800,                    'pattern' => '/^[0-9]+$/'),
                       'seamless' => array('value' => 'seamless',             'pattern' => '/^[a-z]+$/'),
                       'name'     => array('value' => hash($hashmtd, $input), 'pattern' => '/^[a-zA-Z0-9]+$/'),
                      'sandbox'   => array('value' => 'allow-same-origin allow-scripts', 
                                           'pattern' => '/^(allow\-(forms|scripting|pointer|popups|same\-origin|scripts|top\-navigation){1}\s*)*$/')
			);
// copy options to iframe and check validity
    $iframe = array();
    foreach ($iframeopt as $option => $check) {
      $iframe[$option] = (key_exists($option, $argv) ? $argv[$option]: $iframeopt[$option]['value']);         
      if (!preg_match($iframeopt[$option]['pattern'], $iframe[$option])) $err = true;
    }
// create Shiny app
    $outputopt = array('data'   => array('output' => 'tableOutput',        'render' => 'renderDataTable'), 
                       'image'  => array('output' => 'plotOutput',         'render' => 'renderImage'), 
                       'plot'   => array('output' => 'plotOutput',         'render' => 'renderPlot'), 
                       'print'  => array('output' => 'verbatimTextOutput', 'render' => 'renderPrint'), 
                       'table'  => array('output' => 'tableOutput',        'render' => 'renderTable'), 
                       'text'   => array('output' => 'textOutput',         'render' => 'renderText'), 
                       'verb'   => array('output' => 'verbatimTextOutput', 'render' => 'renderPrint')
			);
    if (key_exists('output', $argv) && key_exists($argv['output'], $outputopt)) {
      $output = $outputopt[$argv['output']]['output'];
      $render = $outputopt[$argv['output']]['render'];
      $out    = 'output$out';
      $prg    = <<< EOT
  library("shiny")

  shinyApp(
    ui = fluidPage(
      $output("out")
    ),
    server = function(input, output) {
      $out <- $render({
        $input
      })
    }
  )
EOT;
    } else {
      $prg = $input;
    } 
    if (!$err) {
      $path = $wgShinyAppDir . '/' . htmlspecialchars($wgSitename) . '/' . htmlspecialchars($parser->getTitle()) . '/' . $iframe['name'];
      mkdir($path, 0777, true);
      file_put_contents($path . '/app.R', $prg);
      $iframe['src'] = $wgShinyUrl . '/' . htmlspecialchars($wgSitename) . '/' . htmlspecialchars($parser->getTitle()) . '/' . $iframe['name'] . '/';
    }
    $ret = '<iframe ';
    foreach ($iframe as $key => $value) {
      $ret .= $key . '="' . $value . '" ';
    }
    $ret .= ">\n" . ($err ? $prg : 'Sorry, your browser does not support iframes') . "\n</iframe>";
    if ($err) {
      $ret = htmlspecialchars($ret); 
    } 
    return $ret;
  }

}

?>
